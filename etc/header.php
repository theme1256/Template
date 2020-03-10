<?php
	require_once(__DIR__ . "/common.php");
?>
<!DOCTYPE html>
<html lang="<?= $lang;?>" class="h-100">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<!-- The above 2 meta tags *must* come first in the head; any other head content must come *after* these tags -->

		<title><?= $site['name'];?></title>
		<meta name="description" content="">
		<meta name="keywords" content="">
		<link rel="shortcut icon" type="image/png" href="/favicon.png"/>
		<!-- Cache Control -->
		<meta http-equiv="Cache-control" content="public, max-age=86400">
		<!-- Chrome tab color -->
		<meta name="theme-color" content="#343A40" />
		<!-- Windows Phone -->
		<meta name="msapplication-navbutton-color" content="#343A40" />
		<!-- iOS Safari -->
		<meta name="apple-mobile-web-app-status-bar-style" content="#343A40" />

		<!-- jQuery -->
		<?= $Content->js(NODE . "jquery/dist/jquery.min.js", ["inline" => true]);?>

		<!--[if lt IE 9]>
		<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
		<![endif]-->

		<!-- Bootstrap -->
		<?= $Content->css(NODE . "bootstrap/dist/css/bootstrap.min.css");?>
		<?= $Content->js(NODE . "bootstrap/dist/js/bootstrap.min.js");?>
		<!-- FontAwesome -->
		<?= $Content->css(NODE . "@fortawesome/fontawesome-free/css/all.min.css");?>
		<!-- Gritter -->
		<?= $Content->css(NODE . "gritter/css/jquery.gritter.css");?>
		<?= $Content->js(NODE . "gritter/js/jquery.gritter.js");?>
		<!-- Sweet Alert -->
		<?= $Content->css(NODE . "sweetalert2/dist/sweetalert2.min.css");?>
		<?= $Content->js(NODE . "sweetalert2/dist/sweetalert2.min.js");?>
		<!-- Custom JS -->
		<script type="text/javascript">
			// En global variabel som bruges til at se om en klient skal have extra information i console.log el.l.
			const DEBUG = <?= (DEBUG ? "true" : "false");?>,
				OS = "<?= (IOS ? "ios" : (ANDROID ? "android" : "computer"))?>",
				DEVICE = "<?= (TABLET ? "tablet" : (MOBILE ? "mobile" : "computer"))?>"

			// En wrapper til at lave AJAX kald, så man ikke skal håndtere opsætning og fejl hver gang
			let call = ($url, $data, _success, _fail = null, $type = "POST", extra = null) => {
				if(DEBUG)
					console.log($data)
				let conf = {
					url: $url,
					type: $type,
					dataType: 'json',
					crossDomain: true,
					data: $data,
				}
				if (extra != null) {
					$.each(extra, (key, value) => {
						conf[key] = value
					})
				}
				$.ajax(
					conf
				).done((d) => {
					if (d.status == "success") {
						_success(d)
					} else {
						notify(d.message, d.status)
						if (_fail != null)
							_fail(d)
					}
				}).fail((d) => {
					if (DEBUG)
						console.log(d)

					if (d.status == 404) {
						const msg = "Filen blev ikke fundet på serveren."
					} else if (d.status == 403) {
						const msg = "Du har ikke adgang til at kalde det script."
					} else if (d.status == 500) {
						const msg = "Der skete en fejl på serveren. (500)"
					} else {
						const msg = "Fejl: " + d.status + ", " + d.statusText
					}

					notify(msg, "danger")
					if (_fail != null)
						_fail(d)
				})
			}
			// Opretter en notifikation gennem gritter pluginet
			// Udviddet til at understøtte danger, warning og success
			let notify = (msg, type, sticky = false) => {
				$.gritter.add({
					text: msg,
					class_name: 'gritter-'+type,
					sticky: sticky,
				})
			}
			// Konverterer .serializeArray() til et Object som $.ajax (eller call()) kan bruge til noget
			let objectifyForm = (inp) => {
				let rObject = {}
				for (let i = 0; i < inp.length; i++) {
					if (inp[i]['name'].substr(inp[i]['name'].length - 2) == "[]") {
						const tmp = inp[i]['name'].substr(0, inp[i]['name'].length-2)
						if (Array.isArray(rObject[tmp])) {
							rObject[tmp].push(inp[i]['value'])
						} else {
							rObject[tmp] = []
							rObject[tmp].push(inp[i]['value'])
						}
					} else {
						rObject[inp[i]['name']] = inp[i]['value']
					}
				}
				return rObject
			}

			// En måde at validere inputs og eftersom JavaScript ikke understøtter pointers, så er der en global variabel E, som tæller fejl
			// Husk at nulstille E i starten af en validering
			let E = 0
			let helptext = (obj, text, parent = ".form-group") => {
				let p = obj.closest(parent)
				p.find('small').text(text)
			}
			let validate = (obj, parent = ".form-group") => {
				let p = obj.closest(parent)
				p.removeClass("has-error")
				p.removeClass("has-warning")
				p.removeClass("has-success")
				const val = obj.val()
				if (val == "" || !val || val.length === 0 || val == 0) {
					p.addClass("has-error")
					E++
					helptext(obj, "Dette felt skal være udfyldt")
					return false
				} else {
					p.addClass("has-success")
					return val
				}
			}

			// Noget kode som fanger form-submit og retter det til et AJAX kald
			$(() => {
				$("form").submit(function(e) {
					e.preventDefault()
					const $this = $(e.currentTarget)
					// Fin knappen der blev klikket på
					let btn = $this.find("button.btn[clicked=true]")
					// Hent teksten i den knap
					const btn_text = btn.text()
					// Opdater knappen til en spinner
					btn.html('<i class="far fa-spinner fa-spin fa-fw"></i>')
					btn.attr("disabled", "disabled")
					// Hent url der skal sendes data til og metoden
					const url = $this.attr("action")
					const method = $this.attr("method")
					
					// Hent data fra formen
					E = 0
					let Data = objectifyForm($this.serializeArray())
					$this.find("input").each((index, el) => {
						let e = $(el)
						const required = e.attr("required")
						if (typeof attr !== typeof undefined && attr !== false)
							validate(e)
					})
					Data.method = "ajax"
					const after = (Data.return === undefined ? false : Data.return)
					// Send data til API
					call(
						url,
						Data,
						(d) => {
							setTimeout(() => {
								if (after)
									location.href = after
								else
									location.reload()
							}, 1000)
							btn.html(btn_text)
							btn.removeAttr("disabled")
							notify(d.message, d.status)
						},
						(d) => {
							btn.html(btn_text)
							btn.removeAttr("disabled")
						},
						method,
					)
				})
				$("form button.btn").click((e) => {
					$("button", $(e.currentTarget).parents("form")).removeAttr("clicked")
					$(this).attr("clicked", "true")
				})
			})
		</script>
		<!-- Custom CSS -->
		<style type="text/css">
			main > .container { padding: 60px 15px 0; }
			.footer { background-color: #f5f5f5; }
			.footer > .container { padding-right: 15px; padding-left: 15px; }
			img{ max-width: 100%; }

			/* Gritter stuff */
			.gritter-danger > div, .gritter-warning > div, .gritter-success > div{ background: none !important; }
			.gritter-danger > div.gritter-item, .gritter-warning > div.gritter-item, .gritter-success > div.gritter-item{ color: #eee !important; font-size: 13px !important; }
			.gritter-danger{ background-color: rgba(217, 83, 79, .8) !important; }
			.gritter-warning{ background-color: rgba(240, 173, 78, .8) !important; }
			.gritter-success{ background-color: rgba(92, 184, 92, .8) !important; }

			/* En ekstra tæt tabel */
			.table-very-condensed > thead > tr > th,
			.table-very-condensed > tbody > tr > th,
			.table-very-condensed > tfoot > tr > th,
			.table-very-condensed > thead > tr > td,
			.table-very-condensed > tbody > tr > td,
			.table-very-condensed > tfoot > tr > td{
			    padding: 5px 10px !important;
			}
		</style>
	</head>
	<body class="d-flex flex-column h-100">
		<header>
			<!-- Fixed navbar -->
			<nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
				<a class="navbar-brand" href="#">Fixed navbar</a>
				<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>
				<div class="collapse navbar-collapse" id="navbarCollapse">
					<ul class="navbar-nav mr-auto">
						<li class="nav-item active">
							<a class="nav-link" href="#">Home <span class="sr-only">(current)</span></a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="#">Link</a>
						</li>
						<li class="nav-item">
							<a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Disabled</a>
						</li>
					</ul>
					<form class="form-inline mt-2 mt-md-0">
						<input class="form-control mr-sm-2" type="text" placeholder="Search" aria-label="Search">
						<button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button>
					</form>
				</div>
			</nav>
		</header>
		<main role="main" class="flex-shrink-0">
			<div class="container">