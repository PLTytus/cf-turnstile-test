<!DOCTYPE html>
<html>
	<head>
		<title>Turnstile Test</title>
		<style type="text/css">
			* {
				box-sizing: border-box;
			}
			body {
				margin: 5px;
				padding: 0;
				background: #2d2d2d;
				color: white;
			}
			form {
				display: grid;
				grid-template-columns: repeat(3, auto);
				grid-auto-rows: min-content;
				gap: 5px;
			}
			.label {
				text-align: right;
			}
			.desc {
				color: #c1c1c1;
				overflow: hidden;
				white-space: nowrap;
				text-overflow: ellipsis;
			}
			input, select, textarea {
				color: black;
			}
			textarea {
				resize: none;
				height: 100px;
			}
			input[type=button], input[type=submit], #widget_container {
				grid-column: 2;
			}
			input[type=button], input[type=submit] {
				cursor: pointer;
				height: 3em;
			}
			#widget_container {
				text-align: center;
			}
			.uselect {
				user-select: all;
				font-family: sans-serif;
			}
		</style>

		<script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"></script>

		<script type="text/javascript">
			var FIRST = true;

			getWidget = (button) => {
				let f = button.form;

				let options = {};

				[...f.elements].filter(input => input.name && input.name != "secretkey" && input.name != f["response-field-name".value]).forEach(input => {
					switch(input.type){
						case "radio":
							options[input.name] = +input.value ? true : false;
							break;

						default:
							if(input.value){
								if(input.tagName == "TEXTAREA"){
									options[input.name] = (e) => eval(input.value.trim());
								} else {
									options[input.name] = input.value.trim();
								}
							}
							break;
					}
				});

				if(FIRST){
					FIRST = false;
				} else {
					turnstile.remove("#widget");
				}
				turnstile.render("#widget", options);
			};

			document.onreadystatechange = () => {
				if(document.readyState != "complete") return;

				const _POST = <? echo json_encode($_POST) ?>;

				console.log(_POST);

				Object.entries(_POST).forEach(([k, v]) => {
					if(document.forms.theForm.elements[k]){
						document.forms.theForm[k].value = v;
					}
				});
			};
		</script>
	</head>



	<body>
		<?php
			if(count($_POST)){
				$secret = !empty($_POST["secrekey"]) ? $_POST["secrekey"] : "1x0000000000000000000000000000000AA";
				$response = !empty($_POST["response-field-name"]) ? $_POST["response-field-name"] : "cf-turnstile-response";
				$response = !empty($_POST[$response]) ? $_POST[$response] : false;

				if($response){
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, "https://challenges.cloudflare.com/turnstile/v0/siteverify");
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
					curl_setopt($ch, CURLOPT_POSTFIELDS, [
						"secret" => $secret,
						"response" => $response,
						"remoteip" => $_SERVER["REMOTE_ADDR"],
					]);

					$server_output = curl_exec($ch);
					$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

					curl_close($ch);

					echo "<pre>";
						echo "HTTP $httpCode " . json_encode(json_decode($server_output), JSON_PRETTY_PRINT);
					echo "</pre>";
				} else {
					echo "<font color=red>NO CAPTCHA DATA</font>";
				}
				echo "<hr>";
			}
		?>

		<section>
			<b>Test keys:</b><br>
			Public:<br>
			&ndash; <span class="uselect">1x00000000000000000000AA</span> &ndash; Always passes &ndash; visible<br>
			&ndash; <span class="uselect">2x00000000000000000000AB</span> &ndash; Always blocks &ndash; visible<br>
			&ndash; <span class="uselect">1x00000000000000000000BB</span> &ndash; Always passes &ndash; invisible<br>
			&ndash; <span class="uselect">2x00000000000000000000BB</span> &ndash; Always blocks &ndash; invisible<br>
			&ndash; <span class="uselect">3x00000000000000000000FF</span> &ndash; Forces an interactive challenge &ndash; visible<br>
			Secret:<br>
			&ndash; <span class="uselect">1x0000000000000000000000000000000AA</span> &ndash; Always passes<br>
			&ndash; <span class="uselect">2x0000000000000000000000000000000AA</span> &ndash; Always fails<br>
			&ndash; <span class="uselect">3x0000000000000000000000000000000AA</span> - Yields a "token already spent" error<br>
		</section>

		<hr>

		<form id="theForm" method="POST">
			<input type="button" value="DOCS" onclick="window.open('https://developers.cloudflare.com/turnstile/', '_blank');">

			<span><!-- blank --></span>
			<span><!-- blank --></span>
			<span><!-- blank --></span>
			<span><!-- blank --></span>

			<span class="label">sitekey</span>
			<input type="text" name="sitekey" value="1x00000000000000000000AA" style="min-width:200px;" required>
			<span class="desc" title="Every widget has a sitekey. This sitekey is associated with the corresponding widget configuration and is created upon the widget creation.">Every widget has a sitekey. This sitekey is associated with the corresponding widget configuration and is created upon the widget creation.</span>

			<span class="label">secretkey</span>
			<input type="text" name="secretkey" value="1x0000000000000000000000000000000AA" style="min-width:200px;">
			<span class="desc"></span>

			<span class="label">action</span>
			<input type="text" name="action" maxlength="32" oninput="this.value=this.value.replace(/[^0-9A-Za-z\-_]/g, '');">
			<span class="desc" title="A customer value that can be used to differentiate widgets under the same sitekey in analytics and which is returned upon validation. This can only contain up to 32 alphanumeric characters including _ and -.">A customer value that can be used to differentiate widgets under the same sitekey in analytics and which is returned upon validation. This can only contain up to 32 alphanumeric characters including _ and -.</span>

			<span class="label">cData</span>
			<input type="text" name="cData" maxlength="255" oninput="this.value=this.value.replace(/[^0-9A-Za-z\-_]/g, '');">
			<span class="desc" title="A customer payload that can be used to attach customer data to the challenge throughout its issuance and which is returned upon validation. This can only contain up to 255 alphanumeric characters including _ and -.">A customer payload that can be used to attach customer data to the challenge throughout its issuance and which is returned upon validation. This can only contain up to 255 alphanumeric characters including _ and -.</span>

			<span class="label">callback</span>
			<textarea placeholder="use e variable to receive data from callback" name="callback"></textarea>
			<span class="desc" title="A JavaScript callback invoked upon success of the challenge. The callback is passed a token that can be validated.">A JavaScript callback invoked upon success of the challenge. The callback is passed a token that can be validated.</span>

			<span class="label">error-callback</span>
			<textarea placeholder="use e variable to receive data from callback" name="error-callback"></textarea>
			<span class="desc" title="A JavaScript callback invoked when there is an error (e.g. network error or the challenge failed). Refer to Client-side errors.">A JavaScript callback invoked when there is an error (e.g. network error or the challenge failed). Refer to Client-side errors.</span>

			<span class="label">execution</span>
			<select name="execution">
				<option value="render">render</otpion>
				<option value="execute">execute</otpion>
			</select>
			<span class="desc" title="Execution controls when to obtain the token of the widget and can be on render (default) or on execute. Refer to Execution Modes for more information.">Execution controls when to obtain the token of the widget and can be on render (default) or on execute. Refer to Execution Modes for more information.</span>

			<span class="label">expired-callback</span>
			<textarea placeholder="use e variable to receive data from callback" name="expired-callback"></textarea>
			<span class="desc" title="A JavaScript callback invoked when the token expires and does not reset the widget.">A JavaScript callback invoked when the token expires and does not reset the widget.</span>

			<span class="label">before-interactive-callback</span>
			<textarea placeholder="use e variable to receive data from callback" name="before-interactive-callback"></textarea>
			<span class="desc" title="A JavaScript callback invoked before the challenge enters interactive mode.">A JavaScript callback invoked before the challenge enters interactive mode.</span>

			<span class="label">after-interactive-callback</span>
			<textarea placeholder="use e variable to receive data from callback" name="after-interactive-callback"></textarea>
			<span class="desc" title="A JavaScript callback invoked when challenge has left interactive mode.">A JavaScript callback invoked when challenge has left interactive mode.</span>

			<span class="label">unsupported-callback</span>
			<textarea placeholder="use e variable to receive data from callback" name="unsupported-callback"></textarea>
			<span class="desc" title="A JavaScript callback invoked when a given client/browser is not supported by Turnstile.">A JavaScript callback invoked when a given client/browser is not supported by Turnstile.</span>

			<span class="label">theme</span>
			<select name="theme">
				<option value="auto">auto</otpion>
				<option value="light">light</otpion>
				<option value="dark">dark</otpion>
			</select>
			<span class="desc" title="The widget theme. Can take the following values: light, dark, auto. The default is auto, which respects the user preference. This can be forced to light or dark by setting the theme accordingly.">The widget theme. Can take the following values: light, dark, auto. The default is auto, which respects the user preference. This can be forced to light or dark by setting the theme accordingly.</span>

			<span class="label">language</span>
			<select name="language">
				<option value="auto">auto</otpion>
				<option value="ar-eg">Arabic (Egypt)</option>
				<option value="bg-bg">Bulgarian (Bulgaria)</option>
				<option value="zh-cn">Chinese (Simplified, China)</option>
				<option value="zh-tw">Chinese (Traditional, Taiwan)</option>
				<option value="hr-hr">Croatian (Croatia)</option>
				<option value="cs-cz">Czech (Czech Republic)</option>
				<option value="da-dk">Danish (Denmark)</option>
				<option value="nl-nl">Dutch (Netherlands)</option>
				<option value="en-us">English (United States)</option>
				<option value="fa-ir">Farsi (Iran)</option>
				<option value="fi-fi">Finnish (Finland)</option>
				<option value="fr-fr">French (France)</option>
				<option value="de-de">German (Germany)</option>
				<option value="el-gr">Greek (Greece)</option>
				<option value="he-il">Hebrew (Israel)</option>
				<option value="hi-in">Hindi (India)</option>
				<option value="hu-hu">Hungarian (Hungary)</option>
				<option value="id-id">Indonesian (Indonesia)</option>
				<option value="it-it">Italian (Italy)</option>
				<option value="ja-jp">Japanese (Japan)</option>
				<option value="tlh">Klingon (Qo'noS)</option>
				<option value="ko-kr">Korean (Korea)</option>
				<option value="lt-lt">Lithuanian (Lithuania)</option>
				<option value="ms-my">Malay (Malaysia)</option>
				<option value="nb-no">Norwegian Bokmål (Norway)</option>
				<option value="pl-pl">Polish (Poland)</option>
				<option value="pt-br">Portuguese (Brazil)</option>
				<option value="ro-ro">Romanian (Romania)</option>
				<option value="ru-ru">Russian (Russia)</option>
				<option value="sr-ba">Serbian (Bosnia and Herzegovina)</option>
				<option value="sk-sk">Slovak (Slovakia)</option>
				<option value="sl-si">Slovenian (Slovenia)</option>
				<option value="es-es">Spanish (Spain)</option>
				<option value="sv-se">Swedish (Sweden)</option>
				<option value="tl-ph">Tagalog (Philippines)</option>
				<option value="th-th">Thai (Thailand)</option>
				<option value="tr-tr">Turkish (Turkey)</option>
				<option value="uk-ua">Ukrainian (Ukraine)</option>
				<option value="vi-vn">Vietnamese (Vietnam)</option>
			</select>
			<span class="desc" title="Language to display, must be either: auto (default) to use the language that the visitor has chosen, or an ISO 639-1 two-letter language code (e.g. en) or language and country code (e.g. en-US). Refer to the list of supported languages for more information.">Language to display, must be either: auto (default) to use the language that the visitor has chosen, or an ISO 639-1 two-letter language code (e.g. en) or language and country code (e.g. en-US). Refer to the list of supported languages for more information.</span>

			<span class="label">tabindex</span>
			<input type="number" name="tabindex">
			<span class="desc" title="The tabindex of Turnstile's iframe for accessibility purposes. The default value is 0.">The tabindex of Turnstile's iframe for accessibility purposes. The default value is 0.</span>

			<span class="label">timeout-callback</span>
			<textarea placeholder="use e variable to receive data from callback" name="timeout-callback"></textarea>
			<span class="desc" title="A JavaScript callback invoked when the challenge presents an interactive challenge but was not solved within a given time. A callback will reset the widget to allow a visitor to solve the challenge again.">A JavaScript callback invoked when the challenge presents an interactive challenge but was not solved within a given time. A callback will reset the widget to allow a visitor to solve the challenge again.</span>

			<span class="label">response-field</span>
			<span>
				<label><input type="radio" name="response-field" value="0"> false</label>
				<label><input type="radio" name="response-field" value="1" checked> true</label>
			</span>
			<span class="desc" title="A boolean that controls if an input element with the response token is created, defaults to true.">A boolean that controls if an input element with the response token is created, defaults to true.</span>

			<span class="label">response-field-name</span>
			<input type="text" name="response-field-name" value="cf-turnstile-response">
			<span class="desc" title="Name of the input element, defaults to cf-turnstile-response.">Name of the input element, defaults to cf-turnstile-response.</span>

			<span class="label">size</span>
			<select name="size">
				<option value="normal">normal</otpion>
				<option value="flexible">flexible</option>
				<option value="compact">compact</otpion>
			</select>
			<span class="desc" title="The widget size. Can take the following values: normal, flexible, compact.">The widget size. Can take the following values: normal, flexible, compact.</span>

			<span class="label">retry</span>
			<select name="retry">
				<option value="auto">auto</otpion>
				<option value="never">never</otpion>
			</select>
			<span class="desc" title="Controls whether the widget should automatically retry to obtain a token if it did not succeed. The default is auto, which will retry automatically. This can be set to never to disable retry on failure.">Controls whether the widget should automatically retry to obtain a token if it did not succeed. The default is auto, which will retry automatically. This can be set to never to disable retry on failure.</span>

			<span class="label">retry-interval</span>
			<input type="number" name="retry-interval" value="8000" min="1" max="899999">
			<span class="desc" title="When retry is set to auto, retry-interval controls the time between retry attempts in milliseconds. Value must be a positive integer less than 900000, defaults to 8000.">When retry is set to auto, retry-interval controls the time between retry attempts in milliseconds. Value must be a positive integer less than 900000, defaults to 8000.</span>

			<span class="label">refresh-expired</span>
			<select name="refresh-expired">
				<option value="auto">auto</otpion>
				<option value="manual">manual</otpion>
				<option value="never">never</otpion>
			</select>
			<span class="desc" title="Automatically refreshes the token when it expires. Can take auto, manual, or never, defaults to auto.">Automatically refreshes the token when it expires. Can take auto, manual, or never, defaults to auto.</span>

			<span class="label">refresh-timeout</span>
			<select name="refresh-timeout">
				<option value="auto">auto</otpion>
				<option value="manual">manual</otpion>
				<option value="never">never</otpion>
			</select>
			<span class="desc" title="Controls whether the widget should automatically refresh upon entering an interactive challenge and observing a timeout. Can take auto (automatically refreshes upon encountering an interactive timeout), manual (prompts the visitor to manually refresh) or never (will show a timeout), defaults to auto. Only applies to widgets of mode managed.">Controls whether the widget should automatically refresh upon entering an interactive challenge and observing a timeout. Can take auto (automatically refreshes upon encountering an interactive timeout), manual (prompts the visitor to manually refresh) or never (will show a timeout), defaults to auto. Only applies to widgets of mode managed.</span>

			<span class="label">appearance</span>
			<select name="appearance">
				<option value="always">always</otpion>
				<option value="execute">execute</otpion>
				<option value="interaction-only">interaction-only</otpion>
			</select>
			<span class="desc" title="Appearance controls when the widget is visible. It can be always (default), execute, or interaction-only. Refer to Appearance modes for more information.">Appearance controls when the widget is visible. It can be always (default), execute, or interaction-only. Refer to Appearance modes for more information.</span>

			<span class="label">feedback-enabled</span>
			<span>
				<label><input type="radio" name="feedback-enabled" value="0"> false</label>
				<label><input type="radio" name="feedback-enabled" value="1" checked> true</label>
			</span>
			<span class="desc" title="Allows Cloudflare to gather visitor feedback upon widget failure. It can be true (default) or false.">Allows Cloudflare to gather visitor feedback upon widget failure. It can be true (default) or false.</span>

			<div id="widget_container">
				<div id="widget"></div>
			</div>

			<input type="button" value="GET WIDGET" onclick="getWidget(this);">
			<input type="submit" value="VALIDATE">
		</form>
	</body>
</html>