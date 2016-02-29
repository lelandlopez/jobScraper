<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Goutte\Client;

class CyberCoderScraper {

	private $client;

	function __construct() {
		$client = new Client();
	}

	function start() {
		$jobPage = new JobPage($this->client, 'http://www.cybercoders.com/search/php-skills/california-area-jobs/');
		$jobPage->scrapePage();
	}	
}

class JobPage {
	private $url;	
	private $client;

	function __construct($client, $url) {
		$this->client = $client;
		$this->url = $url;
	}

	public function scrapePage() {
		$crawler = $this->client->request('GET', $this->url);
		$crawler->filter('.job-listing-item')->each(function ($node) {
			$jobStatus = $node->filter('.job-status div')->first()->attr('class');
			if($jobStatus != "status-item applied") {
				$linkCrawler = $node->filter('.job-details-container .job-title > a')->link();
				$jobTitle = $node->filter('.job-details-container .job-title')->text();
				$link = $linkCrawler->getUri();
				$oneliner = $node->filter('.job-details-container .details .one-liner')->text();
				$description = $node->filter('.job-details-container .description ')->text();
				$job = new Job($jobTitle, $link, $oneliner, $description);
				if($job->jobOK() == 2) {
					$job->printJob();
				}
				if($job->jobOK() == 1) {
					$job->printJobPreferred();
				}
			}
		});
		$nextLink = $crawler->filter('.next')->link();
		return $nextLink->getUri();

	}
}

class Job {
	private $jobTitle;
	private $link;
	private $oneliner;
	private $description;

	function __construct($jobTitle, $link, $oneliner, $description) {
		$this->jobTitle = $jobTitle;
		$this->link = $link;
		$this->oneliner = $oneliner;
		$this->description = $description;
	}

	public function jobOK() {
		$badWords = [
			"Senior",
			"Sr.",
			"Sr",
			"Lead",
		];

		$goodWords = [
			"Jr",
			"Jr.",
			"Junior",
			"junior",
			"Entry",
			"entry",
			
		];

		for($i = 0; $i < count($badWords); $i++) {
			if (strpos($this->jobTitle, $badWords[$i]) !== false) {
				return 0;
			}

			if (strpos($this->oneliner, $badWords[$i]) !== false) {
				return 0;
			}

			if (strpos($this->description, $badWords[$i]) !== false) {
				return 0;
			}
		}


		for($i = 0; $i < count($goodWords); $i++) {
			if (strpos($this->jobTitle, $goodWords[$i]) !== false) {
				return 1;
			}

			if (strpos($this->oneliner, $goodWords[$i]) !== false) {
				return 1;
			}

			if (strpos($this->description, $goodWords[$i]) !== false) {
				return 1;
			}
		}
		return 2;

	}

	public function printJob() {
		print "<a target='_blank' href=" . $this->link . ">" . $this->jobTitle . "</a>";
		print "<br>";
	}

	public function printJobPreferred() {
		print "<a target='_blank' href=" . $this->link . ">" . $this->jobTitle . "</a> <b>Preffered</b>";
		print "<br>";
	}

}

class JobScraperController extends Controller
{
	public function getJobs() {

		$client = new Client();

		$crawler = $client->request('GET', 'https://www.cybercoders.com/login/');
		$form = $crawler->selectButton('Log in')->form();
		$crawler = $client->submit($form, array('Email' => 'lelandlopez@gmail.com', 'Password' => 'Waiakea2015!'));
		$crawler->filter('.flash-error')->each(function ($node) {
			print $node->text()."\n";
		});	

		$jobPage = new JobPage($client, 'http://www.cybercoders.com/search/php-skills/california-area-jobs/');
		$nextUri = $jobPage->scrapePage();
		for($i = 0; $i < 10; $i++) {
			echo $i . "asdfasdfasdf<br>";
			$jobPage = new JobPage($client, $nextUri);
			$nextUri = $jobPage->scrapePage();
		}

	}



}
