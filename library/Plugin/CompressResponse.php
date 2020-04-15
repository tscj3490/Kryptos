<?php 
class Plugin_CompressResponse extends Zend_Controller_Plugin_Abstract
{
	protected $timestart;
	public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
        $this->timestart = microtime(true);
    }
    

	public function dispatchLoopShutdown()
    {
        $content = $this->getResponse()->getBody();
		$app = "<!-- before ".strlen($content).", after ";
        /**$content = preg_replace(
            array(
                '/(\x20{2,})/',   // extra-white spaces
                '/\t/',           // tab
                '/\n\r/'          // blank lines
            ),
            array(' ', '', ''),
            $content
        );*/
        $app .= strlen($content)." -->";
        // if the browser does not support gzip, serve the stripped content
        if (@strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') === FALSE) {
            $this->getResponse()->setBody($content.$app);
        }
        else {
            //header('Content-Encoding: gzip');
             $this->getResponse()->setHeader('Content-Type', 'text/html; charset=UTF-8'); 
             $this->getResponse()->setHeader('Accept-encoding', 'gzip,deflate');
             $this->getResponse()->setHeader('Vary', 'Accept-Encoding');
             $this->getResponse()->setHeader('Content-Encoding', 'gzip');
             $tpl_source = gzencode($content,9);
             $kompresja = strlen($tpl_source)*100/strlen($this->getResponse()->getBody());
             
             $time = microtime(true) - $this->timestart;
             
             
             $tpl_source = gzencode($content.'<!-- kompresja orginal size: '.strlen($this->getResponse()->getBody()).'B, after compression '.strlen($tpl_source).'B '.$kompresja.'%, '.$time.'s -->',9);
             $this->getResponse()->setHeader('Content-Length', strlen($tpl_source));
             
             //exit;// echo '---'.strlen($content);exit;
             $this->getResponse()->setBody($tpl_source);
        }
    }

}
?>