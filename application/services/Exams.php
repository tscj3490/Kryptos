<?php

class Application_Service_Exams
{
    /** @var self */
    protected static $_instance = null;
    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? new self : self::$_instance; }

    const TYPE_GLOBAL = 1;
    const TYPE_LOCAL = 2;

    const TYPES_DISPLAY = [
        1 => [
            'id' => 1,
            'label' => 'Globalny',
        ],
        [
            'id' => 2,
            'label' => 'Lokalny',
        ],
    ];

    /** @var Application_Model_Exams */
    protected $examsModel;

    /** @var Application_Model_ExamsSessions  */
    protected $examsSessionsModel;

    /** @var Application_Model_ExamsQuestions */
    protected $questionsModel;

    /** @var Application_Model_ExamsQuestionsAnswers */
    protected $answersModel;

    private function __construct()
    {
        self::$_instance = $this;

        $this->examsModel = Application_Service_Utilities::getModel('Exams');
        $this->examsSessionsModel = Application_Service_Utilities::getModel('ExamsSessions');
        $this->questionsModel = Application_Service_Utilities::getModel('ExamsQuestions');
        $this->answersModel = Application_Service_Utilities::getModel('ExamsQuestionsAnswers');
    }

    public function save($data)
    {
        $examData = array_merge(array(
            'status' => 1,
            'type' => 2,
        ), $data['exam']);

        $exam = $this->examsModel->save($examData);

        if (isset($data['questions'])) {
            $this->saveQuestions($exam->id, $data['questions']);
        }

        return $exam;
    }

    public function loadQuestionsFromExcel($fileUri)
    {
        require_once 'PHPExcel/IOFactory.php';
        $objPHPExcel = PHPExcel_IOFactory::load($fileUri);

        $questions = array();
        $question = null;

        /** get all the worksheets from the excel file */
        foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {

            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);

            /* leave out the heading i.e first row */
            for ($row = 1; $row <= $highestRow; ++$row) {
                $col1 = trim($worksheet->getCellByColumnAndRow(0, $row)->getValue());
                $col2 = trim($worksheet->getCellByColumnAndRow(1, $row)->getValue());

                if (!empty($col1) && empty($col2)) { // question row
                    if (!empty($question)) {
                        $questions[] = $question;
                    }

                    $question = array(
                        'question' => $col1,
                        'answers' => array(),
                    );
                } elseif (!empty($col2)) { // answer row
                    $question['answers'][] = array(
                        'answer' => $col2,
                        'is_correct' => $col1 === 'x',
                    );
                } else { // empty row
                    continue;
                }
            }
        }
        if (!empty($question)) {
            $questions[] = $question;
        }

        $objPHPExcel->disconnectWorksheets();

        return $questions;
    }

    public function exportQuestionsToExcel($exam)
    {
        $this->getFull($exam);
        require_once 'PHPExcel/IOFactory.php';
        //$objPHPExcel = PHPExcel_IOFactory::load($fileUri);
    }

    public function saveQuestions($examId, $questions)
    {
        $questionOrder = 0;

        $questionsIds = array();
        $questionsCurrent = $this->questionsModel->getList(array('exam_id = ?' => $examId));
        if (!empty($questionsCurrent)) {
            foreach ($questionsCurrent as $question) {
                $questionsIds[] = $question['id'];
            }
            $this->questionsModel->delete(array('exam_id = ?' => $examId));
            $this->answersModel->delete(array('question_id IN (?)' => $questionsIds));
        }

        foreach ($questions as $questionData) {
            $answerOrder = 0;
            $questionOrder++;
            $questionData['exam_id'] = $examId;
            $questionData['order'] = $questionOrder;
            $question = $this->questionsModel->save($questionData);

            if (isset($questionData['answers'])) {
                foreach ($questionData['answers'] as $answerData) {
                    $answerOrder++;
                    $answerData['order'] = $answerOrder;
                    $answerData['question_id'] = $question->id;
                    $this->answersModel->save($answerData);
                }
            }
        }

        $this->examsModel->update(['questions_count' => $questionOrder], ['id' => $examId]);
    }

    public function createSession($examId, $userId, $data = [])
    {
        $course = $this->examsModel->requestObject($examId);
        $examData = array_merge(array(
            'user_id' => $userId,
            'exam_id' => $examId,
            'is_done' => 0,
            'type' => $course->type,
        ), $data);

        $session = $this->examsSessionsModel->save($examData);

        return $session;
    }

    public function getSession($sessionId)
    {
        $session = $this->examsSessionsModel->requestObject($sessionId);
        $exam = $this->getFull($session['exam_id']);

        if ($exam['type'] == self::TYPE_LOCAL || Application_Service_Utilities::getAppType() === 'hq_data') {
            $session = $session->toArray();
            $session['exam'] = $exam;
        } else {
            $globalSession = Application_Service_Utilities::apiCall('hq_courses', 'api/get-exam-session', [
                'session_id' => $session->unique_id,
                'exam_id' => $exam['unique_id'],
                'user_id' => $session->user_id,
                'app_id' => Application_Service_Utilities::getAppId(),
            ]);

            if (null === $session->unique_id) {
                $session->unique_id = $globalSession['session']['unique_id'];
                $session->save();
            }

            $session = $session->toArray();
            $session['exam'] = $exam;
            $session['exam']['questions'] = $globalSession['session']['exam']['questions'];
        }

        return $session;
    }

    public function getFull($examId)
    {
        $exam = $this->examsModel->getOne(['e.id = ?' => $examId]);
        $exam->questions = $this->questionsModel->getList(array('eq.exam_id = ?' => $exam['id']));
        $this->answersModel->injectObjectsCustom('id', 'answers', 'question_id', array('eqa.question_id IN (?)' => null), $exam->questions, 'getList', true);

        return $exam;
    }

    public function sessionComplete($sessionId, $data)
    {
        $session = $this->getSession($sessionId);
        $questionsSummary = array();
        $exam = $session['exam'];
        $answers = array();

        if ($session['type'] == self::TYPE_GLOBAL && Application_Service_Utilities::getAppType() !== 'hq_data') {
            $apiResult = Application_Service_Utilities::apiCall('hq_courses', 'api/complete-exam-session', [
                'session_id' => $session['unique_id'],
                'data' => $data,
            ]);

            if (!is_array($apiResult)) {
                Throw new Exception('Serwer API nie odpowiada', 500);
            }

            $result = $apiResult['result'];
            $correctAnswersCount = $apiResult['correct_count'];
        } else {
            foreach ($exam['questions'] as $question) {
                $questionId = $question['id'];
                $goodAnswers = array();
                $requestAnswers = array();
                foreach ($question['answers'] as $answer) {
                    if ($answer['is_correct'] === '1') {
                        $goodAnswers[] = (int) $answer['id'];
                    }
                }
                foreach ($data['answers'][$questionId] as $answerId => $checked) {
                    if ($checked) {
                        $requestAnswers[] = $answerId;
                    }
                }

                sort($goodAnswers);
                sort($requestAnswers);

                $questionsSummary[$questionId] = $goodAnswers === $requestAnswers;
                $answers[$questionId] = $requestAnswers;
            }

            $correctAnswersCount = array_sum($questionsSummary);
            $result = $correctAnswersCount >= $exam['required_to_pass'];
        }


        $session['is_done'] = 1;
        $session['result'] = $result;
        $session['correct_count'] = $correctAnswersCount;
        $session['done_date'] = date('Y-m-d H:i:s');
        $session['data'] = json_encode($answers, true);

        $this->examsSessionsModel->save($session);

        return $session;
    }

}