<h4>Request flow</h4>

<p>
<ol>
	<li><code>Core</code> class is loaded.</li>
	<li><code>Core::run()</code> is called, which creates <code>new Tikori()</code> object.</li>
	<li>Configuration using <code>Tikori::reconfigure()</code> is loaded.</li>
	<li>
		<code>Core::setupModules()</code> is called, to preload all modules,
		and config them if <code>modules/&lt;moduleName&gt;/&lt;ModuleName&gt;.php</code> using <code>&lt;ModuleName&gt;::setup()</code>.
	</li>
	<li>Request and response are created.</li>
	<li>Configuring routes using config.</li>
	<li>Dispatching. Routes are checked from newest to oldest, first from last defined module to first, then from default config. Some modules uses default routing, so they can be executed as latest ones.</li>
	<li>Controller is created, and action is called.</li>
	<li>Finalizing. Response is getting <code>status</code>, <code>headers</code> and <code>body</code> which is returned as script output.</li>
</ol>
</p>