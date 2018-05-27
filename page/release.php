<?php

/**
* description: ATK Page
* 
* @author : Gowrav Vishwakarma
* @email : gowravvishwakarma@gmail.com, info@xavoc.com
* @website : http://xepan.org
* 
*/

namespace xepan\epanservices;

class page_release extends \xepan\base\Page {
	public $title='Release XAVOC ERP version';

	function init(){
		parent::init();
		
		$form = $this->add('Form');
		$form->addField('Checkbox','for_in_premises');
		$form->addField('Line','file_name')->set('xepan2.zip')->validate('required');
		$form->addField('Line','version')->set(file_get_contents('../version'));

		$form->addSubmit('GO');

		$vp = $this->add('VirtualPage');
		$vp->set([$this,'makeRelease']);

		if($form->isSubmitted()){			
			$form->js()->univ()->frameURL('Relase process',$this->app->url($vp->getURL(),$form->get()))->execute();
		}
	}

	function makeRelease($page){
		$this->app->stickyGET('for_in_premises');
		$this->app->stickyGET('file_name');

		$page->add('View_Console')
		->set(function($c){

			set_time_limit(0);

			chdir('..');
			$c->out('In Dir <b>'. getcwd());

			// $c->out('Updating code first');
			// $this->updateGitCode($c);

			$c->out('Removing existing release_files temporary folder');
			$output = shell_exec('rm -r release_files');

			$c->out('Copying to release directory');

			$output = shell_exec('rsync -a . ./release_files --exclude={.git/,/api,/tests,/shared/apps/xavoc,/shared/apps/xepan,/snippet,/websites} 2>&1');
			$c->out($output);
			
			// ========= in release_files directory ============= /
			chdir('release_files');
			$c->out('In Dir <b>'. getcwd());

			$c->out('Removing sysmlinks');

			// remove root symlink
			if(file_exists('./atk4')){
				unlink('./atk4');
			}


			// remove admin symlink
			if(file_exists('./admin/atk4')){
				unlink('./admin/atk4');
			}

			if(file_exists('./admin/vendor')){
				unlink('./admin/vendor');
			}

			if(file_exists('./admin/websites')){
				unlink('./admin/websites');
			}

			if(file_exists('./admin/xepantemplates')){
				unlink('./admin/xepantemplates');
			}

			// remove install symlinks
			if(file_exists('./install/atk4')){
				unlink('./install/atk4');
			}

			if(file_exists('./install/vendor')){
				unlink('./install/vendor');
			}

			if(file_exists('./install/websites')){
				unlink('./install/websites');
			}

			if(file_exists('./install/xepantemplates')){
				unlink('./install/xepantemplates');
			}


			$c->out('Creating websites empty folder with 404 file');
			$output = mkdir('websites');
			$c->out('websites folder creation '.$output);

			file_put_contents('websites/404.html','404');
			
			// remove xprint related files
			$this->rrmdir('vendor/xepan/commerce/templates/js/tool/designer');
			$this->rrmdir('vendor/xepan/commerce/page/designer');


			if(!isset($_GET['for_in_premises'])){
				// remove ACL by substituting with Controller ACtion
				$content = "<?php \n\n namespace xepan\hr; \n\n class Controller_ACL extends Controller_Action { } ";
				file_put_contents('vendor/xepan/hr/lib/Controller/ACL.php', $content);
			}
			// remove hostedserver specific config
			if(file_exists('config.php')) unlink('config.php');


			$version="";
			if($_GET['version']){
				$version='-'.$_GET['version'];
			}

			// update version in file
			if($version){
				file_put_contents('version', $version);
			}

			$file_name = $_GET['file_name'];

			if(file_exists($file_name)) unlink($file_name);

			$zip_cmd= "zip -r $file_name . --exclude *.svn* --exclude *.git* --exclude *.DS_Store* --exclude *.zip*";
			$c->out('<b>'.$zip_cmd.'</b>');
			$output = shell_exec($zip_cmd);
			$c->out("output:<br/> <pre>$output</pre>");

			$c->out('Release file zipped, moving file to root');
			$output = shell_exec("mv $file_name ../$file_name");

			$c->out('File moved, moving self (chdir) to root');
			// =================== end in release_file directory ====================/
			chdir('..');
			$c->out('Removing release_files temporary folder');
			$output = shell_exec('rm -r release_files');

			$c->out('Everything done, new version is ready to download');

		});
	}

	function updateCodeGit($c){
		// In root ??? Confirm ???

		$c->out('In Dir <b>'. getcwd());

		// echo 'resetting to origin/master <br/>';
		// $output= shell_exec('git reset --hard origin/master');
		// echo "output:<br/> <pre>$output</pre>";

		$c->out('Pulling origin master');
		$output= shell_exec('git pull origin master 2>&1');
		$c->out("output:<br/> <pre>$output</pre>");

		$apps = ['accounts','base','blog','cms','commerce','communication','crm','hr','marketing','production','projects','listing'];

		$root=getcwd();
		foreach ($apps as $app) {
			chdir($root);
			chdir('vendor/xepan/'.$app);

			$c->out('In Dir <b>'. getcwd());
			$c->out('Checking out');
			$output= shell_exec('git checkout origin master 2>&1');
			$c->out("output:<br/> <pre>$output</pre>");

			$c->out('resetting to origin/master');
			$output= shell_exec('git reset --hard origin/master 2>&1');
			$c->out("output:<br/> <pre>$output</pre>");

			$c->out('Pulling origin master');
			$output=shell_exec('git pull origin master 2>&1');
			$c->out("output:<br/> <pre>$output</pre>");
		}

		chdir($root);
		chdir('vendor/xepan/atk4');
		$c->out("Updating xepan/atk 4.3");
		$output=shell_exec('git pull origin 4.3 2>&1');
		$c->out("output:<br/> <pre>$output</pre>");

	}

	function rrmdir($dir) {
	   if (is_dir($dir)) {
	     $objects = scandir($dir);
	     foreach ($objects as $object) {
	       if ($object != "." && $object != "..") {
	         if (filetype($dir."/".$object) == "dir") $this->rrmdir($dir."/".$object); else unlink($dir."/".$object);
	       }
	     }
	     reset($objects);
	     rmdir($dir);
	   }
	} 
}
