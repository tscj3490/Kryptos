<?php
	class Muzyka_Youtube
	{
		public static function getVideos($title)
		{
			$l = "http://gdata.youtube.com/feeds/api/videos?q=".urlencode($title)."&orderby=viewCount&start-index=1&max-results=10&v=2&alt=json";
			$arr = '';
			if(function_exists('curl_init') && 1==0)
			{
				$ch = curl_init($l);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_TIMEOUT, 30);			
				$arr = curl_exec($ch);
				curl_close($ch);
			}
			else
			{
				$arr = file_get_contents($l);
			}
			$arr = json_decode($arr);
			$elements = null;
			if(is_object($arr))
			{
				foreach($arr->feed->entry as $el)
				{
					$element = null;
					$title = get_object_vars($el->title);
					$element['title'] = $title['$t'];
					$t= '$t';
					
					$published = get_object_vars($el->published);					
					$element['published'] = date('Y-m-d h:i:s',strtotime($published['$t']));
					
					
					$element['url']		   = $el->content->src;
					$element['type']	   = $el->content->type;
					$vars = get_object_vars($el);
					$mediaGroup = get_object_vars($vars['media$group']);
					$keywords = $mediaGroup['media$keywords']->$t;
					$keywords = preg_replace('/[ ]*,[ ]*/',',', $keywords);
					$element['keywords2']  =  $keywords;
					$element['keywords']  = explode(',',$keywords);					
					$element['duration']  = $mediaGroup['media$content'][0]->duration;
					$element['thumbnail'] = $mediaGroup['media$thumbnail'][0]->url;
					$element['thumb_width'] = $mediaGroup['media$thumbnail'][0]->width;
					$element['thumb_height'] = $mediaGroup['media$thumbnail'][0]->height;
					$elements[] = $element;
					unset($element);
				}
			}
			return $elements;
		}
	}