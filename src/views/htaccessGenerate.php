<h1>Image directory <em>.htaccess</em> generation</h1>

<p>Each directory pointed to by a <code>StaticImageDisplayer</code> instance must contain a
.<code>.htaccess</code> file. Click on the button below to to generate this file.
The target directory is <code><?php echo plainstring_to_htmlprotected($this->targetDir) ?></code>
and is relative to the ROOT_PATH of your application.</p>

<form action="createHtAccess" method="post">
<div class="form-actions">
	<input type="hidden" name="name" value="<?php echo plainstring_to_htmlprotected($this->instanceName) ?>" />
	<button type="submit" class="btn btn-primary">Generate <em>.htaccess</em> file.</button>
</div>
</form>