<?php
	$this->breadcrumbs = array(
		'Installation',
	);
?>

<h4>1. Installation</h4>

<p>To install Tikori 5:</p>
<ol>
	<li>Simply unzip framework and place it somewhere on your server.</li>
	<li>Open <code>index.php</code> in root of your webpage (move existing one from framework directory if needed).</li>
	<li>Change path in <code>include()</code> function to point framework unzipped <code>core/Core.php</code> path.</li>
	<li>Open webpage in a browser.</li>
	<li>It's working! Simple, isn't it?</li>
</ol>

<h4>Initial setup</h4>

<p>Tikori 5 works out of box. Generally it may work in 3 ways:</p>
<ul>
	<li><strong>Static</strong> - you only need to create own design and put static content files</li>
	<li><strong>Dynamic</strong> - you can create controllers to have more flexibility</li>
	<li><strong>Dynamic with Database</strong> - Content (CMS) managing, user accounts and community are available. You can use own modules, or include default ones.</li>
</ul>
