<h4>Results:</h4>
<div class="results">
	<p>Number of pages crawled: <b><?php echo $this->numPagesCrawled ?></b></p>
	<p>Number of unique images: <b><?php echo $this->numImages?></b></p>
	<p>Number of unique internal links: <b><?php echo count($this->seenInternalLinksList)?></b></p>
	<p>Number of unique external links: <b><?php echo count($this->seenExternalLinksList)?></b></p>
	<p>Average page load in seconds: <b><?php echo  $averagePageLoadSpeed?></b></p>
	<p>Average word count: <b><?php echo  $averageWordCount?></b></p>
	<p>Average title length: <b><?php echo  $averageTitleLength?></b></p>

	<div class="report-item">
		<h5>Crawled pages:</h5>
		<div>
			<ul>
				<?php
					foreach($this->crawledPagesList as $page => $code) {
						echo "<li><a href='".$page."' target='_blank'>".$page."</a><span class='http-status'> (HTTP status code: <b>".$code."</b>)</span></li>";
					}
				?>
			</ul>
		</div>
	</div>

	<div class="report-item">
		<h5>Unique images:</h5>
		<div>
			<ul>
				<?php
					foreach($this->seenImagesList as $imageUrl => $flag) {
						// Inline SVG should be listed without link
						if (strpos($imageUrl, "data:image") !== false) {
							echo "<li>".$imageUrl."</li>";
							continue;
						}
						$imageUrl = 'https://agencyanalytics.com' . $imageUrl;
						echo "<li><a href='".htmlspecialchars($imageUrl)."' target='_blank'>".$imageUrl."</a></li>";
					}
				?>
			</ul>
		</div>
	</div>

	<div class="report-item">
		<h5>Unique internal links:</h5>
		<div>
			<ul>
				<?php
					foreach($this->seenInternalLinksList as $link => $flag) {
						// Interanal links may or may not have domain
						if (strpos($link, "https://agencyanalytics.com") === false) {
							$link = 'https://agencyanalytics.com' . $link;
						}
						
						echo "<li><a href='".$link."' target='_blank'>".$link."</a></li>";
					}
				?>
			</ul>
		</div>
	</div>

	<div class="report-item">
		<h5>Unique external links:</h5>
		<div>
			<ul>
				<?php
					foreach($this->seenExternalLinksList as $link => $flag) {
						echo "<li><a href='".$link."' target='_blank'>".$link."</a></li>";
					}
				?>
			</ul>
		</div>
	</div>
</div>