<?php

namespace Gini\Controller\CLI;

class Index extends \Gini\Controller\CLI
{

	private function _rcFile()
	{
		return getcwd().'/.ginirc';
	}

	public function __index($args)
	{
		echo "gini index who\n";
		echo "gini index login <user>\n";
		echo "gini index logout\n";
		echo "gini index publish <version>\n";
		echo "gini index unpublish <version>\n";
		echo "gini index search <keyword>\n";
	}

	public function actionLogin($args)
	{
		if (count($args) > 0) {
			$username = trim($args[0]);
		} else {
            $username = readline('User: ');
		}

        echo 'Password: ';
        `stty -echo`;
        $password = rtrim(fgets(STDIN), "\n");
        `stty echo`;
        echo "\n";

        $config = [
        	'username' => $username
        ];
        
        $uri = $_SERVER['GINI_INDEX_URI'] ?: 'http://gini-index.genee.cn/';

        try {
	        $rpc = new \Gini\RPC(rtrim($uri, '/').'/api');
	        $config['token'] = $rpc->createToken($username, $password);
	        file_put_contents($this->_rcFile(), $config);

			echo "You've successfully logged in as $username.\n";
        } catch (\Gini\RPC\Exception $e) {

        }
	}

	public function actionLogout($args)
	{
		$rc = $this->_rcFile();
		if (file_exists($rc)) {
			unlink($rc);
		}

		echo "You are logged out now.\n";
	}

	public function actionWho($args)
	{
		$rc = $this->_rcFile();
		if (file_exists($rc)) {
	        $content = file_get_contents($rc);
	        $config = (array) yaml_parse($content);
		}

        if (isset($config['username'])) {
        	echo "Hey! You are \e[33m".$config['username']."\e[0m!\n";
        } else {
        	echo "Oops. You are \e[33mNOBODY\e[0m!\n";
        }
	}

    public function actionPublish($argv)
    {
        count($argv) > 0 or die("Usage: gini index publish <version>\n\n");

        $appId = APP_ID;
        // TODO: publish current module to gini-index.genee.cn
        $version = $argv[0];
        $GIT_DIR = escapeshellarg(APP_PATH.'/.git');
        $command = "git --git-dir=$GIT_DIR archive $version --format tgz 2> /dev/null";

        $path = "$appId/$version.tgz";
        $ph = popen($command, 'r');
        if (is_resource($ph)) {

            $content = '';
            while (!feof($ph)) {
                $content .= fread($ph, 4096);
            }

            if (strlen($content) == 0) {
                die("\e[31mError: $appId/$version missing!\e[0m\n");
            }

            $uri = $_SERVER['GINI_INDEX_URI'] ?: 'http://gini-index.genee.cn/';

            // sometimes people will run publish before run composer
            if (!class_exists('\Sabre\DAV\Client')) {
                require_once SYS_PATH.'/vendor/autoload.php';
            }

            $userName = readline('User: ');
            echo 'Password: ';
            `stty -echo`;
            $password = rtrim(fgets(STDIN), "\n");
            `stty echo`;
            echo "\n";

            $options = [
                'baseUri' => $uri,
                'userName' => $userName,
                'password' => $password,
            ];

            $client = new \Sabre\DAV\Client($options);
            $response = $client->request('MKCOL', $appId);
            if ($response['statusCode'] == 401 && isset($response['headers']['www-authenticate'])) {
                // Authentication required
                // 'Authorization: Basic '. base64_encode("user:password")
                die ("Failed to publish $appId/$version.\n");
            }

            $response = $client->request('PUT', $path, $content);
            if ($response['statusCode'] >= 200 && $response['statusCode'] <= 206) {
                echo "$appId/$version was published successfully.\n";
            } else {
                die ("Failed to publish $appId/$version.\n");
            }

            pclose($ph);
        }

    }

    public function actionUnpublish($argv)
    {
        count($argv) > 0 or die("Usage: gini index unpublish <version>\n\n");

        $version = $argv[0];
        $appId = APP_ID;
        $path = "$appId/$version.tgz";

        $uri = $_SERVER['GINI_INDEX_URI'] ?: 'http://gini-index.genee.cn/';

        if (!class_exists('\Sabre\DAV\Client')) {
            require_once SYS_PATH.'/vendor/autoload.php';
        }

        $userName = readline('User: ');
        echo 'Password: ';
        `stty -echo`;
        $password = rtrim(fgets(STDIN), "\n");
        `stty echo`;
        echo "\n";

        $options = [
            'baseUri' => $uri,
            'userName' => $userName,
            'password' => $password,
        ];

        $client = new \Sabre\DAV\Client($options);
        $response = $client->request('HEAD', $path);
        if ($response['statusCode'] == 200) {
            echo "Unpublishing $appId/$version...\n";
            $response = $client->request('DELETE', $path);
            if ($response['statusCode'] >= 200 && $response <= 206) {
                echo "done.\n";
            } else {
                echo "failed.\n";
            }
        } else {
            echo "Failed to find $path\n";
        }

    }

}