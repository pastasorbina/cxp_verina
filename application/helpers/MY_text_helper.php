<?php

	function makeLipsum( $count=1 ) {
		$string = "<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur malesuada mollis leo, hendrerit ornare neque molestie eget. Aliquam felis elit, varius id condimentum quis, eleifend et erat. Suspendisse rhoncus felis eu elit iaculis bibendum. In ac ipsum sit amet felis egestas sagittis et ac eros. Proin vel eros id ipsum interdum ornare nec vel orci. Morbi scelerisque dui nec felis vulputate convallis. Integer molestie risus mattis turpis posuere fermentum eu quis tortor. Vestibulum quis enim vitae augue tempus semper vel auctor eros. Vestibulum eleifend lobortis nunc, id euismod velit facilisis at. Integer venenatis nulla a lorem suscipit lacinia. Duis pellentesque odio mauris, et dictum lectus.</p>";
		$text = '';
		for($i=0; $i<$count; $i++){
			$text .= $string;
		}
		return $text;
	}

?>
