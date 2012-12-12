<h4>Controllers &amp; Routes</h4>

<p>To set new route, you can use following code:</p>

<pre class="prettyprint lang-php">
Route::set('default', '(&lt;controller&gt;(/&lt;action&gt;(/&lt;id&gt;)))')
->defaults(array(
    'controller' => 'Welcome',
    'action'     => 'index',
));
</pre>

<h5>Syntax</h5>
<p>
	<code class="prettyprint lang-php">Route::set(name, uri [,options]);</code>
</p>

<h5>Name</h5>
<p><code>name</code> is unique identifier</p>

<h5>URI</h5>
<p>The uri is a string that represents the format of urls that should be matched.
	The tokens surrounded with <code>&lt;&gt;</code> are keys and anything surrounded with <code>()</code> are optional parts of the uri.
	In Tikori routes, any character is allowed and treated literally aside from <tt>()&lt;&gt;</tt>.
The <code>/</code> has no meaning besides being a character that must match in the uri.
Usually the <code>/</code> is used as a static seperator but as long as the regex makes sense, there are no restrictions to how you can format your routes.</p>

<h5>Options</h5>
<p>In options you can specify additoinal rules of parsing URI segment. For example, if action should be one of add,edit,update,delete, you can pass:
<pre>
array('action' => '(add|edit|update|delete)')
</pre>
If you want that (last) param will include any character until end of URI, even /, then you can use <tt>.*</tt> regex.
<pre class="prettyprint lang-php">
Route::set('default', '(&lt;controller&gt;(/&lt;something&gt;))',
	array('something' => '.*'))
->defaults(array(
    'controller' => 'Default',
    'action'     => 'index',
));
</pre>
Now if you provide path like: <code>http://myapp.com/path/to/here/and/there</code>, route will match
controller <tt>path</tt>, action <tt>index</tt>, and param something will be <tt>to/here/and/there</tt>.
</p>

<h5>Defaults</h5>

<blockquote>Either your route uses controller and action or not, those params must be always provided!</blockquote>