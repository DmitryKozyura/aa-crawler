<form method="post">
	<h4>Enter an entry point (e.g. https://agencyanalytics.com):</h4>
	<input
		type="text"
		name="entry_url"
		placeholder="https://agencyanalytics.com/"
		<?php if ($this->urlToCrawl) echo 'value="'.$this->urlToCrawl.'"'; ?>
		required
	>
	<button>Submit</button>
	<?php if ($errorMessage) { echo '<div class="error">'. $errorMessage .'</div>'; } ?>
</form>