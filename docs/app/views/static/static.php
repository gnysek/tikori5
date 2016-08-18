<h4>3. Static application</h4>

<p>
    If your application is configured as static, you can place content in <kbd>*.php</kbd> files under
    <code>app/views/static</code> and they will be accesed by <kbd>static/(filename)</kbd> param in URL by <code>StaticController</code>.
    This documentation uses <kbd>static</kbd> mode to display it's content.
</p>

<blockquote>When using static app, you cannot use modules folder, and modules configuration will be skipped.
</blockquote>

<p><strong>Example:</strong></p>

<p>
    Calling:<br/>
    <code>http://myapp.com/index.php?p=static/show/me/page</code><br/>
    <small>or with nice url's: <code>http://myapp.com/static/show/me/page</code></small>
    <br/>
    will display content of:<br/>
    <code>app/view/static/show/me/page.php</code><br/>
    using<br/>
    <code>layout.default.php</code>.
</p>


<h5>Default static view</h5>

<p>
    The content of <kbd>static/static_default.php</kbd> will be displayed as default page when no path in URL.<br/>
    To change it, extend StaticController and override <kbd>public $defaultPath</kbd> param:

<pre>
    class OwnStaticController extends StaticController {
        public $defaultPath = 'template_name';
    }
</pre>

    If file don't exists, 404 error will be displayed.
</p>
