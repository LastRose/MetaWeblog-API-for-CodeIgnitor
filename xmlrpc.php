<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

class Xmlrpc extends CI_Controller {

	function __construct() {
		parent::__construct();
	}

	function index() {
		$config['functions']['blogger.getUserInfo'] = array('function' => 'xmlrpc.getUserInfo');
		$config['functions']['blogger.getUsersBlogs'] = array('function' => 'xmlrpc.getUsersBlogs');
		$config['functions']['blogger.deletePost'] = array('function' => 'xmlrpc.deletePost');
		$config['functions']['metaWeblog.newPost'] = array('function' => 'xmlrpc.newPost');
		$config['functions']['metaWeblog.editPost'] = array('function' => 'xmlrpc.editPost');
		$config['functions']['metaWeblog.getPost'] = array('function' => 'xmlrpc.getPost');
		$config['functions']['metaWeblog.getCategories'] = array('function' => 'xmlrpc.getCategories');
		$config['functions']['metaWeblog.getRecentPosts'] = array('function' => 'xmlrpc.getRecentPosts');
		$config['functions']['metaWeblog.newMediaObject'] = array('function' => 'xmlrpc.newMediaObject');
		$config['object'] = $this;
		$this->xmlrpcs->initialize($config);
		$this->xmlrpcs->serve();
	}

	/*This is a Unit Testing function*/
	function test2() {
		$server_url = site_url('xmlrpc');
		$this->xmlrpc->set_debug(TRUE);
		$this->xmlrpc->server($server_url, 80);
		$this->xmlrpc->method('metaWeblog.editPost');

		$request = array(
			'1',
			'user',
			'pass',
			array(
				array(
					'title' => 'Test',
					'description' => 'test',
					'categories' => array(array('test cat'), 'struct')
				), 'struct'
			),
			'1'
		);
		$this->xmlrpc->request($request);

		if (!$this->xmlrpc->send_request()) {
			echo $this->xmlrpc->display_error();
		}
		else
		{
			echo '<pre>';
			print_r($this->xmlrpc->display_response());
			echo '</pre>';
		}
	}

	function login($user, $pass) {
		$u = 'user';
		$p = 'pass';
		if ($u == $user AND $p == $pass) {
			return true;
		}
		return false;
	}

	function getUsersBlogs($request) {
		$parameters = $request->output_parameters();
		if (!$this->login($parameters['1'], $parameters['2'])) {
			return $this->xmlrpc->send_error_message('100', 'Invalid Access');
		}
		$blogs = $this->blogs->getBlogs();
		foreach ($blogs as $blog) {
			$array[] = array(
				array(
					'url' => array(site_url(), 'string'),
					'blogid' => array($blog['blogId'], 'string'),
					'blogName' => array($blog['BlogName'], 'string')
				),
				'struct'
			);
		}
		$response = array($array, 'array');
		return $this->xmlrpc->send_response($response);
	}

	function getCategories($request) {
		$parameters = $request->output_parameters();
		if (!$this->login($parameters['1'], $parameters['2'])) {
			return $this->xmlrpc->send_error_message('100', 'Invalid Access');
		}
		$blogid = $parameters['0'];
		$categories = $this->blogs->getCats($blogid);
		foreach ($categories as $cat) {
			$array[] = array(
				array(
					'categoryId' => array($cat['catId'], 'string'),
					'title' => array($cat['category'], 'string'),
					'description' => array($cat['category'], 'string'),
					'htmlUrl' => array(site_url(), 'string'),
					'rssUrl' => array(site_url(), 'string'),
				), 'struct'
			);
		}
		$response = array($array, 'array');
		return $this->xmlrpc->send_response($response);
	}

	function getUserInfo($request) { //todo
		$parameters = $request->output_parameters();
		if (!$this->login($parameters['1'], $parameters['2'])) {
			return $this->xmlrpc->send_error_message('100', 'Invalid Access');
		}
		$response = array(
			array(
				'nickname' => array('Your Name', 'string'),
				'userid' => array('userid', 'string'),
				'url' => array('http://your-awsome-website.com', 'string'),
				'email' => array('your@email.com', 'string'),
				'lastname' => array('Last Name', 'string'),
				'firstname' => array('First Name', 'string'),
			), 'struct'
		);
		return $this->xmlrpc->send_response($response);
	}

	function getRecentPosts($request) {
		$parameters = $request->output_parameters();
		if (!$this->login($parameters['1'], $parameters['2'])) {
			return $this->xmlrpc->send_error_message('100', 'Invalid Access');
		}
		$blogid = $parameters['0'];
		$numposts = $parameters['3'];
		$blogs = $this->blogs->getRecent($blogid, $numposts, '');
		foreach ($blogs as $blog) {
			$cats = $this->blogs->getPostCats($blog['postId']);
			foreach ($cats as $cat) {
				$category[] = array($cat['category'], 'string');
			}
			$categories = array($category, 'array');
			$array[] = array(
				array(
					'postid' => array($blog['postId'], 'string'),
					'dateCreated' => array(standard_date('DATE_ISO8601', mysql_to_unix($blog['date'])), 'dateTime.iso8601'),
					'title' => array($blog['title'], 'string'),
					'description' => array($blog['description'], 'string'),
					'categories' => $categories,
					'publish' => array($blog['published'], 'boolean'),
				),
				'struct'
			);
		}
		$response = array($array, 'array');
		return $this->xmlrpc->send_response($response);
	}

	function getPost($request) {
		$parameters = $request->output_parameters();
		if (!$this->login($parameters['1'], $parameters['2'])) {
			return $this->xmlrpc->send_error_message('100', 'Invalid Access');
		}
		$postid = $parameters['0'];
		$blogs = $this->blogs->getPost_id($postid);
		foreach ($blogs as $blog) {
			$cats = $this->blogs->getPostCats($postid);
			foreach ($cats as $cat) {
				$category[] = array($cat['category'], 'string');
			}
			$categories = array($category, 'array');
			$array = array(
				array(
					'postid' => array($blog['postId'], 'string'),
					'dateCreated' => array(standard_date('DATE_ISO8601', mysql_to_unix($blog['date'])), 'dateTime.iso8601'),
					'title' => array($blog['title'], 'string'),
					'description' => array($blog['description'], 'string'),
					'categories' => $categories,
					'publish' => array($blog['published'], 'boolean'),
				),
				'struct'
			);
		}
		$response = $array;
		return $this->xmlrpc->send_response($response);
	}

	function newPost($request) {
		$parameters = $request->output_parameters();
		if (!$this->login($parameters['1'], $parameters['2'])) {
			return $this->xmlrpc->send_error_message('100', 'Invalid Access');
		}
		$blogid = $parameters['0'];
		$content = $parameters['3'];
		$publish = $parameters['4'];
		$insert = array(
			'blogId' => $blogid,
			'title' => $content['title'],
			'user_id' => $user_id,
			'date' => mdate("%Y-%m-%d %H:%i:%s"),
			'description' => $content['description'],
			'published' => $publish,
			'url_friendly' => url_title($content['title']),
			'publishDate' => (isset($content['dateCreated']) && $content['dateCreated'] ?
					mdate("%Y-%m-%d %H:%i:%s", strtotime($content['dateCreated'])) :
					mdate("%Y-%m-%d %H:%i:%s")),
		);
		if (($insertid = $this->blogs->newPost($insert)) && write_file('images/entry.txt', print_r($parameters, true))) {
			foreach ($content['categories'] as $cat) {
				$this->blogs->newPostCat($insertid, $cat);
			}
			$response = array($insertid, 'string');
			return $this->xmlrpc->send_response($response);
		}
		return $this->xmlrpc->send_error_message('1', 'Failed to Post');
	}

	function editPost($request) {
		$parameters = $request->output_parameters();
		if (!$this->login($parameters['1'], $parameters['2'])) {
			return $this->xmlrpc->send_error_message('100', 'Invalid Access');
		}
		$postid = $parameters['0'];
		$content = $parameters['3'];
		$publish = $parameters['4'];
		$insert = array(
			'title' => $content['title'],
			'updated' => mdate("%Y-%m-%d %H:%i:%s"),
			'description' => $content['description'],
			'published' => $publish,
			'url_friendly' => url_title($content['title']),
			'publishDate' => (isset($content['dateCreated']) && $content['dateCreated'] ?
					mdate("%Y-%m-%d %H:%i:%s", strtotime($content['dateCreated'])) :
					mdate("%Y-%m-%d %H:%i:%s")),
		);
		if ($this->blogs->editPost($postid, $insert) && $file = write_file('images/entry.txt', print_r($parameters, true))) {
			$categories = (isset($content['categories']) && $content['categories'] ? $content['categories'] : '');
			$this->blogs->updatePostCat($postid, $categories);
			$response = array(true, 'boolean');
		} else {
			$response = array(false, 'boolean');
		}
		return $this->xmlrpc->send_response($response);
	}

	function deletePost($request) {
		$parameters = $request->output_parameters();
		if (!$this->login($parameters['2'], $parameters['3'])) {
			return $this->xmlrpc->send_error_message('100', 'Invalid Access');
		}
		$this->blogs->deletePost($parameters['1']);
		$response = array(true, 'boolean');
		return $this->xmlrpc->send_response($response);
	}

	function newMediaObject($request) {
		$parameters = $request->output_parameters();
		if (!$this->login($parameters['1'], $parameters['2'])) {
			return $this->xmlrpc->send_error_message('100', 'Invalid Access');
		}
		$blogid = $parameters['0'];
		$file = $parameters['3'];
		$filename = $file['name'];
		$filename = substr($filename, (strrpos($filename, "/") ? strrpos($filename, "/") + 1 : 0));
		if (write_file('images/blog/' . $filename, $file['bits'])) {
			$response = array(
				array(
					'url' => array('http://www.lastrose.com/blog/images/blog/' . $filename, 'string')
				), 'struct'
			);
			return $this->xmlrpc->send_response($response);
		}
		return $this->xmlrpc->send_error_message('2', 'File Failed to Write');
	}
}

array(
	array(
		'request' => array('login','string'),
		'username' => array('newuser','string'),
		'password' => array(sha1('123123'),'string')
	),'struct'
);