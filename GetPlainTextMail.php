<?php
// Adapted from http://www.netlash.com/blog/detail/html-naar-plain-text-omvormen

class GetPlainTextMail
{
	public static function getPlainText($text, $includeAHrefs = false, $includeImgAlts = false)
	{
		// replace break rules with a new line and make sure a paragraph also ends with a new line
		$text = str_replace('<br />', PHP_EOL, $text);
		$text = str_replace('</p>', '</p>'. PHP_EOL, $text);

		// remove tabs
		$text = str_replace("\t", '', $text);

		// remove the head- and style-tags and all their contents
		$text = preg_replace('|\<head.*\>(.*\n*)\</head\>|isU', '', $text);
		$text = preg_replace('|\<style.*\>(.*\n*)\</style\>|isU', '', $text);

		// replace links with the inner html of the link with the url between ()
		// eg.: <a href="http://site.domain.com">My site</a> => My site (http://site.domain.com)
		if($includeAHrefs) $text = preg_replace('|<a.*href="(.*)".*>(.*)</a>|isU', '$2 ($1)', $text);

		// replace images with their alternative content
		// eg. <img src="path/to/the/image.jpg" alt="My image" /> => My image
		if($includeImgAlts) $text = preg_replace('|\<img[^>]*alt="(.*)".*/\>|isU', '$1', $text);

		// strip tags
		$text = strip_tags($text);

		// replace 'line feed' characters with a 'line feed carriage return'-pair
		$text = str_replace("\n", "\n\r", $text);

		// replace double, triple, ... line feeds to one new line
		$text = preg_replace('/(\n\r)+/', PHP_EOL, $text);

		// decode html entities
		$text = html_entity_decode($text);

		// return the plain text
		return $text;
	}
}