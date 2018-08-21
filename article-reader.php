<?php

// ERROR REPORTING
error_reporting(E_ALL & ~E_NOTICE);

// SOCKET TIMEOUT
ini_set('default_socket_timeout', 120);

?>


<?php

class ArticleReader {

	var $feed = null;
	var $bannedWords = array();
	
	public function __construct($feedUrl) {
		$this->feed = $this->getFeedContent($feedUrl);
	}
	
	// Add banned words used for articles filtering
	public function addBannedWords($bannedWords) {
		$this->bannedWords = array_merge($bannedWords, $this->bannedWords);
	}
	
	// Returns article data prepared to display on the page
	public function getArticle() {
		$article = $this->getFirstFiltredArticle($this->feed, $this->bannedWords);
		
		return $this->formatArticleData($article);
	}

	// Check if title contains any banned word from given array
	private function containsBannedWord($string, $bannedWords) {
		foreach($bannedWords as $bannedWord) {
			if(strpos($string, $bannedWord) !== false) {
				return true;
			}
		}
		
		return false;
	}

	// Get feed content from given url
	private function getFeedContent($url) {
		$content = file_get_contents($url);
	
		return new SimpleXmlElement($content);
	}

	// Get first article from feed which doesn't contain any banned word
	private function getFirstFiltredArticle($feed, $bannedWords) {
		foreach($feed->channel->item as $entry) {
			if($this->containsBannedWord($entry->title, $bannedWords) === false) {
				return $entry;
			}
		}
	}
	
	// Format article data to make it ready to display on the page
	private function formatArticleData($articleData) {
		$article = array(
			'title' => $articleData->title,
			'author' => $articleData->author,
			'date' => $this->formatDate($articleData->pubDate, 'Y-m-d H:i'),
			'link' => $this->getUrlWithRelativeProtocol($articleData->link) . '?timestamp=' . time()
		);
		
		return $article;
	}

	// Format data string to given format
	private function formatDate($dateString, $format) {
		$date = new DateTime($dateString);
	
		return $date->format($format);
	}

	// Change protocol from http/https to relative one
	private function getUrlWithRelativeProtocol($url) {
		return str_replace(array('http://', 'https://'), '//', $url);
	}
	
}

// CONFIG VARIABLES
$feedUrl = 'http://www.atl.nu/feed/';
$bannedWords = array('trktor', 'djur', 'häst', 'lantbruk');

$articleReader = new ArticleReader($feedUrl);
$articleReader->addBannedWords($bannedWords);
$article = $articleReader->getArticle();

?>

<!doctype html>
<html lang="en-US">
    <head>
        <meta charset="utf-8" />
        <title>LRF - feed task</title>
    </head>
    
    <body>
    	<?php if(!is_null($article)): ?>
		<article>
			<h1>
				<a href="<?php echo $article['link']; ?>"><?php echo $article['title'] ?></a>
			</h1>
			<p>Created by: <strong><?php echo $article['author'] ?></strong> on <datatime><?php echo $article['date']; ?></datatime></p>
		</article>
		<?php else: ?>
			<p>No results</p>
		<?php endif ?>
    </body>
</html>