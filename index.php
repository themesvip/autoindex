<?php

function p($obj)
{
	echo '<pre>';
	print_r($obj);
	echo '</pre>';
}

function copyFile($source, $dest)
{
	$is_dot = array ('.', '..');
	if (is_dir($source))
	{
		if (version_compare(phpversion(), '5.3', '<'))
		{
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($source),
				RecursiveIteratorIterator::SELF_FIRST
			);
		}
		else
		{
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::CHILD_FIRST
			);
		}

		foreach ($iterator as $file)
		{
			if (version_compare(phpversion(), '5.2.17', '<='))
			{
				if (in_array($file->getBasename(), $is_dot))
					continue;
			}
			elseif (version_compare(phpversion(), '5.3', '<'))
			{
				if ($file->isDot())
					continue;
			}

			if ($file->isDir())
				mkdir($dest.DIRECTORY_SEPARATOR.$iterator->getSubPathName(), true);
			else
				copy($file, $dest.DIRECTORY_SEPARATOR.$iterator->getSubPathName());
		}
	}
	else
		copy($source, $dest);

	return true;
}

function addIndex($path, $cli = false)
{
	$is_dot = array ('.', '..');
	if (is_dir($path))
	{
		if (version_compare(phpversion(), '5.3', '<'))
		{
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($path),
				RecursiveIteratorIterator::SELF_FIRST
			);
		}
		else
		{
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::CHILD_FIRST
			);
		}

		foreach ($iterator as $pathname => $file)
		{
			if (version_compare(phpversion(), '5.2.17', '<='))
			{
				if (in_array($file->getBasename(), $is_dot))
					continue;
			}
			elseif (version_compare(phpversion(), '5.3', '<'))
			{
				if ($file->isDot())
					continue;
			}

			$name = (string)trim($file->getFilename());
			$exp = explode('\\', $pathname);
			$dirname = isset($exp[0])? $exp[0].'/' : '';
			if(count($exp) === 2 && $file->isFile())
			{
				if (!file_exists($dirname.'index.php'))
				{
					if (copyFile('sources/index.php', $dirname.'index.php') === true)
						continue;
				}
			}
			else
			{
				if ($file->isDir())
				{
					$dirname = str_replace('\\', '/', $file->getPathname().'/');
					if (!file_exists($dirname.'index.php'))
					{
						if (copyFile('sources/index.php', $dirname.'index.php') === true)
							continue;
					}
				}
			}
		}
		unset($iterator, $file);
		$msg = 'index.php added in '.$path;
		if ($cli === true)
			echo $msg."\n";
		else
			p($msg);
	}
	else
	{
		$msg = $path.' isn\'t a directory';
		if ($cli === true)
			echo $msg."\n";
		else
			p($msg);
	}
}

if (php_sapi_name() === 'cli')
{
	if (isset($argv) &&  (isset($argc) && $argc >= 2))
	{
		array_shift($argv);
		foreach($argv as $dir)
			addIndex($dir, true);
	}
}
elseif (isset($argv) &&  (isset($argc) && $argc < 2))
{
	echo 'Usage: php [directory...]';
	echo "\n\t".'php index.php /var/www/prestashop1609/modules/mymodule/'."\n";
}
else
{
	if(isset($_GET['path']))
	{
		$get_paths = $_GET['path'];
		$paths = explode(',', $get_paths);
		foreach($paths as $path)
			addIndex(trim(strip_tags($path)));
	}
	else
	{
		if(!empty($_POST))
		{
			$get_paths = $_POST['path'];
			$paths = explode(',', $get_paths);
			foreach($paths as $path)
				addIndex(trim(strip_tags($path)));
		}
		else
		{
			echo '<!DOCTYPE html>
			<html lang="en">
			<head>
				<meta charset="utf-8">
				<meta http-equiv="X-UA-Compatible" content="IE=edge">
				<meta name="viewport" content="width=device-width, initial-scale=1">
				<title>Auto Index</title>
				<link href="css/bootstrap.min.css" rel="stylesheet">
				<link href="css/theme.css" rel="stylesheet">
				<link href="http://fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet" type="text/css">
				<link href="http://fonts.googleapis.com/css?family=Lato:400,700,400italic,700italic" rel="stylesheet" type="text/css">
			</head>
			<body id="page-top" class="index">
				<nav class="navbar navbar-default navbar-fixed-top">
					<div class="container">
						<div class="navbar-header page-scroll">
							<a class="navbar-brand">Auto Index</a>
						</div>
					</div>
				</nav>
				<section id="contact">
					<div class="container">
						<div class="row">
							<div class="col-lg-8 col-lg-offset-2">
								<form name="sentMessage" id="contactForm" action="index.php" method="post">
									<div class="row control-group">
										<div class="form-group col-xs-12 floating-label-form-group controls">
											<label>Path to your directory</label>
											<input type="text" class="form-control" placeholder="/var/www/modules/mymodule/" id="path">
											<p class="help-block text-danger"></p>
										</div>
									</div>
									<br>
									<div id="success">
									</div>
									<div class="row">
										<div class="form-group col-xs-12">
											<input type="submit" class="btn btn-success btn-lg" value="Add index" />
										</div>
									</div>
								</form>
							</div>
						</div>
					</div>
				</section>
				<script src="js/jquery.js"></script>
				<script>
					$(".btn").on("click", function (e) {
						e.preventDefault();
						$("#success").html("<pre>Adding index file is in progressing...</pre>");
						$.ajax({
							type: "POST",
							url: "index.php",
							dataType: "html",
							data: {
								path : $("#path").val(),
							},
							success : function(data) {
								$("#success").html(data);
							}
						});
					});
				</script>
			</body>
			</html>';
		}
	}
}