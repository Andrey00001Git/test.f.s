<?php 
	Class Debugger
	{
			// echo 'Начинаем:<br>';
			// echo '<pre>';
			// print_r(Debugger::backtrace());
			// echo '</pre>';
			// echo '<br>Заканчиваем.';
			
		public static function backtrace(){
			$backtrace = debug_backtrace();
			
			$output = '';
			foreach ($backtrace as $bt) {
				$args = '';
				foreach ($bt['args'] as $a) {
					if (!empty($args)) {
						$args .= ', ';
					}
					switch (gettype($a)) {
						case 'integer':
						case 'double':
						$args .= $a;
						break;
						case 'string':
						//$a = htmlspecialchars(substr(, 0, 64)).((strlen($a) > 64) ? '...' : '');
						$args .= "\"$a\"";
						break;
						case 'array':
						$args .= 'Array('.count($a).')';
						break;
						case 'object':
						$args .= 'Object('.get_class($a).')';
						break;
						case 'resource':
						$args .= 'Resource('.strstr($a, '#').')';
						break;
						case 'boolean':
						$args .= $a ? 'TRUE' : 'FALSE';
						break;
						case 'NULL':
						$args .= 'Null';
						break;
						default:
						$args .= 'Unknown';
					}
				}
				$output .= '<br />';
				$output .= '<b>file:</b> '.@$bt['file'].' - line '.@$bt['line'].'<br />';
				$output .= '<b>call:</b> '.@$bt['class'].@$bt['type'].@$bt['function'].'('.$args.')<br />';
			}
			return $output;
		}
	}
?>