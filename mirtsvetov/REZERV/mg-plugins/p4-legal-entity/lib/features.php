<?php

class LegalEntityFeatures
{
	/**
	 * 
	 * Возвращает разницу во времени.
	 * 
	 * @param string $time время, например, 18:00:00.
	 * @param bool $leadingZero использовать формат с ведущим нулём.
	 * @param bool $declension использовать слконения слов.
	 * 
	 * @return string
	 * 
	 */
	public function getTimeDiff($time, $leadingZero = true, $declension = false)
	{
		$diff = strtotime($time) - time();

		if ($diff <= 0) return false;

		if ($leadingZero) {
			$days = str_pad(floor($diff / 86400), 2, 0, STR_PAD_LEFT);
			$hours = str_pad(floor(($diff % 86400) / 3600), 2, 0, STR_PAD_LEFT);
			$minutes = str_pad(floor(($diff % 3600) / 60), 2, 0, STR_PAD_LEFT);
			$seconds = str_pad($diff % 60, 2, 0, STR_PAD_LEFT);
		} else {
			$days = floor($diff / 86400);
			$hours = floor(($diff % 86400) / 3600);
			$minutes = floor(($diff % 3600) / 60);
			$seconds = $diff % 60;
		}
		
		$result = '';

		if ($declension) {
			if ($days > 0) $result .= MG::declensionNum($days, ['день', 'дня', 'дней']) . ' ';
			if ($hours > 0) $result .= MG::declensionNum($hours, ['час', 'часа', 'часов']) . ' ';
			if ($minutes > 0) $result .= MG::declensionNum($minutes, ['минута', 'минуты', 'минут']) . ' ';
			if ($seconds > 0) $result .= MG::declensionNum($seconds, ['секунда', 'секунды', 'секунд']);
		} else {
			if ($days > 0) $result .= $days . ':';
			if ($hours > 0) $result .= $hours . ':';
			if ($minutes > 0) $result .= $minutes . ':';
			if ($seconds > 0) $result .= $seconds;
		}
		
		return $result;
	}

    /**
	 * 
	 * Возвращает склонение слова от числа.
	 * 
	 * @param int $number
	 * @param array $words
	 * 
	 * @return string
	 * 
	 */
	public function declension($number, $words)
    {
		$number = $number % 100;
		if ($number > 19) {
			$number = $number % 10;
		}
		switch ($number) {
			case 1: {
			    return($words[0]);
			}
			case 2: case 3: case 4: {
			    return($words[1]);
			}
			default: {
			    return($words[2]);
			}
		}
	}

	/**
	 * Возвращает отформатированный номер телефона.
	 * 
	 * @param string $phone
	 * 
	 * @return string
	 */
	public function phoneFormat(string $phone): string
	{
	    $phone = trim($phone);

		$phone = preg_replace(
			[
				'/[\+]?([7|8])[-|\s]?\([-|\s]?(\d{3})[-|\s]?\)[-|\s]?(\d{3})[-|\s]?(\d{2})[-|\s]?(\d{2})/',
				'/[\+]?([7|8])[-|\s]?(\d{3})[-|\s]?(\d{3})[-|\s]?(\d{2})[-|\s]?(\d{2})/',
				'/[\+]?([7|8])[-|\s]?\([-|\s]?(\d{4})[-|\s]?\)[-|\s]?(\d{2})[-|\s]?(\d{2})[-|\s]?(\d{2})/',
				'/[\+]?([7|8])[-|\s]?(\d{4})[-|\s]?(\d{2})[-|\s]?(\d{2})[-|\s]?(\d{2})/',	
				'/[\+]?([7|8])[-|\s]?\([-|\s]?(\d{4})[-|\s]?\)[-|\s]?(\d{3})[-|\s]?(\d{3})/',
				'/[\+]?([7|8])[-|\s]?(\d{4})[-|\s]?(\d{3})[-|\s]?(\d{3})/',					
			], 
			[
				'+7 $2 $3-$4-$5', 
				'+7 $2 $3-$4-$5', 
				'+7 $2 $3-$4-$5', 
				'+7 $2 $3-$4-$5', 	
				'+7 $2 $3-$4', 
				'+7 $2 $3-$4', 
			], 
			$phone
		);

		return $phone;
	}
}
