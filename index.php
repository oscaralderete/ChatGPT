<?php
/*
@author: Oscar Alderete <me@oscaralderete.com>
@website: https://oscaralderete.com
*/

define('CHATGPT_API_KEY', '');

if($_SERVER['REQUEST_METHOD'] === 'POST'){
	// if exists POST request
	processPostRequest();
}

// resources
function processPostRequest(){
	$post = getPostData();
	
	$msg = getApiResponse($post);

	echoJson($msg);
}

function getPostData(){
	$str = file_get_contents('php://input');
	return json_decode($str);
}

function getApiResponse($post){
	$msg = [
		'result' => 'ERROR',
		'msg' => 'Oops, an error happened!'
	];

	$get_data = callApi('https://api.openai.com/v1/completions', json_encode([
		'model' => 'text-davinci-003',
		'prompt' => $post->msg,
		'temperature' => 0.5,
		'max_tokens' => 2000
	]));
	$response = json_decode($get_data);

	$msg = [
		'result' => 'OK',
		'msg' => trim($response->choices[0]->text, "\n")
	];
	
	return $msg;
}

function callApi($url, $data){
	$curl = curl_init();
	
	curl_setopt($curl, CURLOPT_POST, 1);
	if($data){
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	}

	// options
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
		'Authorization: Bearer ' . CHATGPT_API_KEY,
		'Content-Type: application/json',
	));
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	
	// execute
	$result = curl_exec($curl);
	if(!$result){
		die("Connection Failure");
	}
	curl_close($curl);
	return $result;
}

function echoJson($msg){
	header('Content-Type: application/json; charset=utf-8');
	die(json_encode($msg));
}

?>
<!doctype html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title>ChatGPT - Pure PHP Implemantation</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
		<link rel="stylesheet" href="css/vue-playground2-chatbox.css"/>
		<link rel="stylesheet" href="css/styles.css"/>
	</head>
	<body>
		<main id="app">
			<p>Basic pure PHP implementation of ChatGPT</p>
			
			<section class="main">
				<div id="msgs" class="chatbox__messages">
					<!--
					chat styles from:
					https://codepen.io/karolsw3/pen/KZmvGG
					-->
				</div>
			</section>
			
			<section class="input">
				<input type="text" v-model="msg" :placeholder="placeholder" @keyup.enter="send" :disabled="waiting">
				<button @click="send" :disabled="waiting">Send</button>
			</section>
		</main>
		
		<script src="https://cdnjs.cloudflare.com/ajax/libs/vue/3.2.45/vue.global.prod.min.js"></script>
		<script>
		const app = Vue.createApp({
			data(){
				return {
					placeholder: 'Say something interesting to ChatGPT...',
					msg: '',
					waiting: false,
				}
			},
			methods: {
				send(){
					if(this.msg.trim() !== ''){
						this.waiting = true;
						this.render(this.msg, 'question')
						
						const self = this;
						const payload = {
							msg: this.msg
						}
						
						this.msg = ''
						fetch('index.php', {
							method: 'POST',
							headers: {
								'Content-Type': 'application/json',
							},
							body: JSON.stringify(payload)
						}).
							then(res => res.json()).
							then(res => {
								console.log(res)
								self.render(res.msg, '')
								self.waiting = false
							}).
							catch(err => {
								console.error('Fetch error!', err)
								self.waiting = false
							})
					}
					else{
						console.log('Please, say something smart...');
					}
				},
				render(str, type = ''){
					const x = document.createElement('div'),
						y = document.createElement('div')
					
					if(type === 'question'){
						x.classList.add('chatbox__messageBox')
						y.classList.add('chatbox__message')
					}
					else{
						x.classList.add('chatbox__messageBox', 'chatbox__messageBox--primary')
						y.classList.add('chatbox__message', 'chatbox__message--primary', 'chatbox__response')
					}
					
					y.innerText = str
					
					x.appendChild(y)
					document.getElementById('msgs').append(x)
				}
			}
		}).mount('#app')
		</script>
	</body>
</html>