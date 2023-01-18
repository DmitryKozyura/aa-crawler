<?php

class Crawler {
	private $urlToCrawl = null;
	private $depth = null;

	private $numPagesCrawled = 0;
	private $indexToCrawl = 0;
	private $numImages = 0;
	private $overallPageLoadTime = 0;
	private $overallWordCount = 0;
	private $overallTitleLength = 0;

	# map: url -> http status code
	private $crawledPagesList = array();

	private $seenImagesList = array();
	private $seenInternalLinksList = array();
	private $seenExternalLinksList = array();

	/**
	 * Crawls data and renders results,
	 * or renders input form
	 */
	public function init() {
		$this->urlToCrawl = false;
		$this->depth = 5;

		if (isset($_POST) && isset($_POST["entry_url"])) {
			// Sanitize and trim input
			$this->urlToCrawl = htmlentities(trim($_POST["entry_url"]));
		}

		if ($this->isUrlValid($this->urlToCrawl)) {
			$this->crawl();
			$this->renderResults();
			return;
		}

		$this->renderForm();
	}

	/**
	 * Crawls required amount of pages
	 */
	public function crawl() {
		while ($this->numPagesCrawled < $this->depth) {
			$this->crawlSinglePage();

			// Stop crawling if we don't have any more pages in DOM
			$this->indexToCrawl++;
			if (count($this->seenInternalLinksList) <= $this->indexToCrawl) {
				break;
			}

			// Assign next page to be crawled
			$seenInternalLinksKeys = array_keys($this->seenInternalLinksList);
			$this->urlToCrawl = 'https://agencyanalytics.com' . $seenInternalLinksKeys[$this->indexToCrawl];
		}
	}

	/**
	 * Crawls a single page
	 */
	private function crawlSinglePage() {
		// Make sure we didn't crawl this page before
		if (array_key_exists($this->urlToCrawl, $this->crawledPagesList)) {
			return false;
		}

		if (array_key_exists($this->urlToCrawl . "/", $this->crawledPagesList)) {
			return false;
		}

		if (array_key_exists(rtrim($this->urlToCrawl, '/'), $this->crawledPagesList)) {
			return false;
		}

		// Request page source
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->urlToCrawl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);
		$requestInfo = curl_getinfo($ch);
		curl_close($ch);

		// Record page load in seconds
		$this->overallPageLoadTime += $requestInfo["total_time"];

		// Build DOM
		$dom = new DOMDocument('1.0');
		@$dom->loadHTML($output);

		// Remove script tags from DOM
		while (($scriptEl = $dom->getElementsByTagName("script")) && $scriptEl->length) {
			$scriptEl->item(0)->parentNode->removeChild($scriptEl->item(0));
		}

		// Count and save images
		$this->getUniqueImgs($dom);

		// Count and save links
		$this->getUniqueLinks($dom);

		// Count and save words
		$this->getWordsNum($dom);

		// Count and save title length
		$this->getTitleLength($dom);

		// Save the page as crawled and increment the counter
		$this->crawledPagesList[$this->urlToCrawl] = $requestInfo["http_code"];
		$this->numPagesCrawled++;
	}

	/**
	 * Counts and saves unique images
	 */
	private function getUniqueImgs($dom) {
		$images = $dom->getElementsByTagName('img');
		foreach ($images as $image) {
			$imageSource = $image->getAttribute('src') ? $image->getAttribute('src') : $image->getAttribute('data-src');
			if ($imageSource && !isset($this->seenImagesList[$imageSource])) {
				$this->seenImagesList[$imageSource] = 1;
				$this->numImages++;
			}
		}
	}

	/**
	 * Counts and saves unique internal and external links
	 */
	private function getUniqueLinks($dom) {
		$links = $dom->getElementsByTagName('a');
		foreach ($links as $link) {
			$linkHref = $link->getAttribute('href');
			if (
				!$linkHref ||
				isset($this->seenInternalLinksList[$linkHref]) ||
				isset($this->seenExternalLinksList[$linkHref])
			) {
				continue;
			}

			if ($this->isLinkInternal($linkHref)) {
				$this->seenInternalLinksList[$linkHref] = 1;
			} else {
				$this->seenExternalLinksList[$linkHref] = 1;
			}
		}
	}

	/**
	 * Counts and saves words
	 */
	private function getWordsNum($dom) {
		$body = $dom->getElementsByTagName('body');
		if ($body && $body->length) {
			// Get contents of the <body> tag
			$body = $body->item(0);
			$bodyString = $dom->savehtml($body);
			// Make sure we have a space between words
			$bodyString = str_replace('><', '> <', $bodyString);
			// Strip tags
			$bodyString = strip_tags($bodyString);
			// Save word count
			$this->overallWordCount += str_word_count($bodyString);
		}
	}

	/**
	 * Counts and saves title length
	 */
	private function getTitleLength($dom) {
		$title = $dom->getElementsByTagName('title');
		if ($title && $title->length) {
			$titleString = $title->item(0)->textContent;
			$this->overallTitleLength += strlen($titleString);
		}
	}

	/**
	 * Renders form
	 */
	private function renderForm() {
		// If we see the form and we already have a url - then the url is invalid
		$errorMessage = $this->urlToCrawl ? "Please enter a valid entry point (e.g. https://agencyanalytics.com)" : false;

		// Show the view
		ob_start();
		include('views/form.php');
		$content = ob_get_clean();

		include 'views/layout.php';
	}

	/**
	 * Renders results
	 */
	private function renderResults() {
		$averagePageLoadSpeed = $this->overallPageLoadTime / $this->numPagesCrawled;
		$averageWordCount = $this->overallWordCount / $this->numPagesCrawled;
		$averageTitleLength = $this->overallTitleLength / $this->numPagesCrawled;

		// Show the view
		ob_start();
		include('views/results.php');
		$content = ob_get_clean();

		include 'views/layout.php';
	}

	/**
	 * Helper method - returns True if a URL is valid for crawling
	 */
	private function isUrlValid($url) {
		if (!$url) {
			return false;
		}

		// Allow only agencyanalytics.com and its subpages to be crawled
		if (strpos($url, "https://agencyanalytics.com") === false) {
			return false;
		}
		
		// Send header to be sure the URL is real
		$headers = @get_headers($url);
		if(!$headers && !strpos($headers[0], '200')) {
			return false;
		}

		return true;
	}

	/**
	 * Helper method - returns True if a URL is internal or False if external
	 */
	private function isLinkInternal($url) {
		// This is a very simple solution and can be easily improved,
		// however, for the given input it does the job.

		// Edge case when we have a host and it's equal to "agencyanalytics.com"
		if (strpos($url, "https://agencyanalytics.com") !== false) {
			return true;
		}

		// If we don't have a host in the url - then url is internal
		$components = parse_url($url);
		return empty($components['host']);
	}
}
