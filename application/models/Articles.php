<?php


/** DISABLED */
	abstract class Application_Model_Articles extends Muzyka_DataModel
	{
		protected $_name = "articles";
		
		public function getArticle($id)
		{
			return $this ->getAdapter()
						 ->select()
						 ->from('articles')
						 ->join('users','articles.idUsers=users.idUsers')
						 ->where('idArticles=?', (int)$id)
						 ->query()
						 ->fetch();				 
		}
		
		public function getArticles()
		{
			return $this ->getAdapter()
						 ->select()
						 ->from('articles')
						 ->join('users','articles.idUsers=users.idUsers')
						 ->order('idArticles DESC')
						 ->query()
						 ->fetchAll();				 
		}
		
		public function search($word)
		{
			$word = mysql_escape_string($word);
			
			try{
			return $this->getAdapter()->query(" SELECT * FROM articles  WHERE content LIKE '%$word%'")->fetchAll();
			}catch (Exception $e){
				echo $e->getMessage();exit;
			}
		}
	}