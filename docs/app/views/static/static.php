<h4>3. Static application</h4>

<p>
	If your application is configured as static, you can place content in <tt>*.php</tt> files under <code>views/static</code> and they will be accesed by <tt>static/path/to/file</tt> param in URL by <code>StaticController</code>.
This documentation uses <tt>static</tt> mode to display it's content.
</p>

<blockquote>When using static app, you cannot use anything from modules folder, and modules configuration will be skipped.</blockquote>

<p><strong>Example:</strong></p>

<p>
	Calling:<br/>
	<code>http://myapp.com/index.php?p=static/show/me/page</code><br/>
	<small>or with nice url's: <code>http://myapp.com/static/show/me/page</code></small><br/>
	will display content of:<br/>
	<code>app/view/static/show/me/page.php</code><br/>
	using<br/>
	<code>layout.default.php</code>.
</p>
