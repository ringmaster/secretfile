<?php

class SecretFilePlugin extends Plugin
{
	public function filter_shortcode_secretfile($content, $code, $attrs, $context, $post) {
		$code = substr(md5($attrs['filename'] . 'secretfile' . $post->id), 0, 8);
		if(!isset($_SESSION['secretfile']) || !is_array($_SESSION['secretfile'])) {
			$_SESSION['secretfile'] = array();
		}
		$attrs['post_id'] = $post->id;
		$_SESSION['secretfile'][$code] = $attrs;
		$link = URL::get(
			'get_secretfile',
			array(
				'post_id' => $post->id,
				'code' => $code,
				'filename' => $attrs['filename'],
			)
		);
		$content = '<a href="' . $link . '">' . $context . '</a>';
		// $content .= '<pre>' . print_r($_SESSION, 1) . '</pre>';
		return $content;
	}

	public function action_init() {
		$this->add_rule('"uploads"/post_id/code/filename', 'get_secretfile');
	}

	/**
	 * @param Theme $theme
	 * @param PluginHandler $handler
	 */
	public function theme_route_get_secretfile($theme, $handler) {
		// Get the whole set of secret files accumulated while browsing during this session
		$secretfiles = Session::get_set('secretfile', false);

		// Get the values from the URL
		$code = $theme->matched_rule->named_arg_values['code'];
		$post_id = $theme->matched_rule->named_arg_values['post_id'];
		$filename = $theme->matched_rule->named_arg_values['filename'];

		// Does the requested secret file exist in the session?
		if(isset($secretfiles[$code])) {
			//Get the media data
			$media = $secretfiles[$code];
			$asset = Media::get($media['hurl']);

			// Does this user have access to the post?
			$post = Post::get(array('id' => $media['post_id']));
			if($post instanceof Post) {
				header('Content-Type: image/jpeg');
				echo $asset->content;
				exit;
			}
		}
		// Redirect to the original post
		Utils::redirect(Post::get(array('id' => $post_id))->permalink);
		exit;
	}
}

?>