<?php
class GoogleAnalyticsWidget extends Widget
{

    public $id = 'UA-XXXXXXX-X';

    public function run()
    {
        echo <<<HTML
<script type="text/javascript">
	var analyticsFileTypes = [''];
	var analyticsEventTracking = 'enabled';
</script>
<script type="text/javascript">
	var _gaq = _gaq || [];
	_gaq.push(['_setAccount', '{$this->id}']);
	_gaq.push(['_trackPageview']);

	(function() {
		var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
        ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
        var s = document.getElementsByTagName('script')[0];
        s.parentNode.insertBefore(ga, s);
	})();
</script>
HTML;

    }
}
