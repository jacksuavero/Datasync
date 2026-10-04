<?php
require_once './config/dbcon.php';
include('./includes/log_helper.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="author" content="DataSync">

<link rel="preconnect" href="https://fonts.gstatic.com">
<link rel="shortcut icon" href="./assets/img/logo-mini.svg">

<title>DataSync - Request Account</title>

<link href="css2.css?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="css/light.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
	* {
		margin: 0;
		padding: 0;
		box-sizing: border-box;
	}

	html, body {
		height: 100%;
		overflow-x: hidden;
	}

	body {
		font-family: 'Inter', sans-serif;
		background: #e3f2fd;
		min-height: 100vh;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 20px;
		opacity: 0;
		transition: opacity 0.4s ease-in;
		position: relative;
	}

	body::before {
		content: '';
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: linear-gradient(135deg, #007BC9 0%, #008FE8 100%);
		filter: blur(100px);
		opacity: 0.3;
		z-index: 0;
	}

	body.loaded {
		opacity: 1;
	}

	.main-container {
		width: 100%;
		max-width: 380px;
		animation: slideUp 0.6s ease-out;
		position: relative;
		z-index: 1;
	}

	@keyframes slideUp {
		from {
			opacity: 0;
			transform: translateY(30px);
		}
		to {
			opacity: 1;
			transform: translateY(0);
		}
	}

	.card {
		background: rgba(255, 255, 255, 0.95);
		backdrop-filter: blur(20px);
		border-radius: 16px;
		box-shadow: 0 20px 60px rgba(0, 123, 201, 0.2);
		padding: 24px 28px;
		border: 1px solid rgba(255, 255, 255, 0.3);
	}

	.header-section {
		text-align: center;
		margin-bottom: 20px;
	}

	.logo-container {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 55px;
		height: 55px;
		background: linear-gradient(135deg, #007BC9 0%, #008FE8 100%);
		border-radius: 14px;
		margin-bottom: 12px;
		box-shadow: 0 8px 20px rgba(0, 123, 201, 0.3);
	}

	.logo-container svg,
	.logo-container img {
		width: 32px;
		height: 32px;
		filter: brightness(0) invert(1);
	}

	.header-section h1 {
		font-size: 20px;
		font-weight: 700;
		color: #1e293b;
		margin-bottom: 4px;
	}

	.header-section p {
		color: #64748b;
		font-size: 13px;
	}

	.form-group {
		margin-bottom: 14px;
	}

	.form-label {
		display: block;
		font-weight: 500;
		color: #334155;
		margin-bottom: 5px;
		font-size: 12px;
	}

	.form-control,
	.form-select {
		width: 100%;
		padding: 10px 12px;
		border: 2px solid #e2e8f0;
		border-radius: 8px;
		font-size: 13px;
		transition: all 0.3s ease;
		background: white;
		font-family: 'Inter', sans-serif;
	}

	.form-control:focus,
	.form-select:focus {
		outline: none;
		border-color: #007BC9;
		box-shadow: 0 0 0 4px rgba(0, 123, 201, 0.1);
	}

	.form-control::placeholder {
		color: #94a3b8;
	}

	.row-group {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 10px;
		margin-bottom: 14px;
	}

	.event-section {
		overflow: hidden;
		max-height: 0;
		opacity: 0;
		transition: all 0.3s ease;
	}

	.event-section.show {
		max-height: 110px;
		opacity: 1;
		margin-bottom: 14px;
	}

	.event-validation {
		margin-top: 6px;
		font-size: 12px;
		display: flex;
		align-items: center;
		gap: 5px;
		animation: fadeIn 0.3s ease;
	}

	@keyframes fadeIn {
		from {
			opacity: 0;
			transform: translateY(-5px);
		}
		to {
			opacity: 1;
			transform: translateY(0);
		}
	}

	.event-validation i {
		font-size: 12px;
	}

	.text-success {
		color: #10b981;
	}

	.text-danger {
		color: #ef4444;
	}

	.text-primary {
		color: #007BC9;
	}

	.btn-submit {
		width: 100%;
		padding: 12px;
		background: linear-gradient(135deg, #007BC9 0%, #008FE8 100%);
		color: white;
		border: none;
		border-radius: 8px;
		font-size: 14px;
		font-weight: 600;
		cursor: pointer;
		transition: all 0.3s ease;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 6px;
	}

	.btn-submit:hover {
		transform: translateY(-2px);
		box-shadow: 0 10px 25px rgba(0, 123, 201, 0.4);
	}

	.btn-submit:active {
		transform: translateY(0);
	}

	.btn-submit:disabled {
		opacity: 0.6;
		cursor: not-allowed;
	}

	.login-link {
		text-align: center;
		margin-top: 16px;
		font-size: 12px;
		color: #64748b;
	}

	.login-link a {
		color: #007BC9;
		text-decoration: none;
		font-weight: 600;
		transition: color 0.2s;
	}

	.login-link a:hover {
		color: #006ba8;
		text-decoration: underline;
	}

	.loading-spinner {
		display: inline-block;
		width: 16px;
		height: 16px;
		border: 2px solid rgba(255, 255, 255, 0.3);
		border-top-color: white;
		border-radius: 50%;
		animation: spin 0.8s linear infinite;
	}

	@keyframes spin {
		to { transform: rotate(360deg); }
	}

	.input-icon {
		position: relative;
	}

	.input-icon i {
		position: absolute;
		left: 12px;
		top: 50%;
		transform: translateY(-50%);
		color: #94a3b8;
		pointer-events: none;
		z-index: 1;
		font-size: 13px;
	}

	.input-icon .form-control,
	.input-icon .form-select {
		padding-left: 36px;
	}

	.success-message {
		background: #d1fae5;
		border: 1px solid #6ee7b7;
		color: #065f46;
		padding: 16px;
		border-radius: 10px;
		margin-bottom: 24px;
		display: flex;
		align-items: center;
		gap: 12px;
		animation: slideDown 0.4s ease-out;
	}

	@keyframes slideDown {
		from {
			opacity: 0;
			transform: translateY(-10px);
		}
		to {
			opacity: 1;
			transform: translateY(0);
		}
	}

	.success-message i {
		font-size: 20px;
	}

	/* Tablet Responsive */
	@media (max-width: 768px) {
		body {
			padding: 30px 16px;
		}

		.card {
			padding: 40px 32px;
		}

		.header-section h1 {
			font-size: 26px;
		}

		.header-section p {
			font-size: 14px;
		}
	}

	/* Mobile Responsive */
	@media (max-width: 576px) {
		body {
			padding: 20px 16px;
			align-items: flex-start;
		}

		.main-container {
			margin-top: 20px;
		}

		.card {
			padding: 32px 24px;
			border-radius: 16px;
		}

		.header-section {
			margin-bottom: 32px;
		}

		.header-section h1 {
			font-size: 24px;
		}

		.header-section p {
			font-size: 13px;
		}

		.logo-container {
			width: 70px;
			height: 70px;
			border-radius: 16px;
			margin-bottom: 16px;
		}

		.logo-container svg,
		.logo-container img {
			width: 40px;
			height: 40px;
		}

		.row-group {
			grid-template-columns: 1fr;
			gap: 0;
		}

		.form-group {
			margin-bottom: 20px;
		}

		.form-label {
			font-size: 13px;
			margin-bottom: 6px;
		}

		.form-control,
		.form-select {
			padding: 12px 14px;
			font-size: 14px;
			border-radius: 8px;
		}

		.input-icon .form-control,
		.input-icon .form-select {
			padding-left: 40px;
		}

		.input-icon i {
			left: 14px;
			font-size: 14px;
		}

		.btn-submit {
			padding: 14px;
			font-size: 15px;
			border-radius: 8px;
		}

		.login-link {
			font-size: 13px;
			margin-top: 20px;
		}

		.event-section.show {
			max-height: 140px;
		}
	}

	/* Small Mobile */
	@media (max-width: 400px) {
		body {
			padding: 16px 12px;
		}

		.card {
			padding: 28px 20px;
		}

		.header-section h1 {
			font-size: 22px;
		}

		.logo-container {
			width: 60px;
			height: 60px;
		}

		.logo-container svg,
		.logo-container img {
			width: 35px;
			height: 35px;
		}

		.form-control,
		.form-select {
			padding: 11px 12px;
			font-size: 13px;
		}

		.input-icon .form-control,
		.input-icon .form-select {
			padding-left: 36px;
		}

		.input-icon i {
			left: 12px;
			font-size: 13px;
		}
	}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body onload="document.body.classList.add('loaded')">

<div class="main-container">
	<div class="card">
		<div class="header-section">
			<div class="logo-container">
				<img src="./assets/img/logo-mini.svg" alt="DataSync Logo">
			</div>
			<h1>Request Account</h1>
			<p>Fill in your details to get started</p>
		</div>

		<form action="request.php" method="post" id="requestForm">
			<!-- Name Fields Row -->
			<div class="row-group">
				<div class="form-group">
					<label for="firstname" class="form-label">First Name</label>
					<div class="input-icon">
						<i class="fas fa-user"></i>
						<input class="form-control" type="text" id="firstname" name="firstname" placeholder="John" required>
					</div>
				</div>

				<div class="form-group">
					<label for="lastname" class="form-label">Last Name</label>
					<div class="input-icon">
						<i class="fas fa-user"></i>
						<input class="form-control" type="text" id="lastname" name="lastname" placeholder="Doe" required>
					</div>
				</div>
			</div>

			<!-- Middle Name -->
			<div class="form-group">
				<label for="middlename" class="form-label">Middle Name</label>
				<div class="input-icon">
					<i class="fas fa-user"></i>
					<input class="form-control" type="text" id="middlename" name="middlename" placeholder="Smith">
				</div>
			</div>

			<!-- Email -->
			<div class="form-group">
				<label for="email" class="form-label">Email Address</label>
				<div class="input-icon">
					<i class="fas fa-envelope"></i>
					<input class="form-control" type="email" id="email" name="email" placeholder="john.doe@example.com" required>
				</div>
			</div>

			<!-- Role -->
			<div class="form-group">
				<label for="role" class="form-label">Your Role</label>
				<div class="input-icon">
					<i class="fas fa-briefcase"></i>
					<select class="form-select" id="role" name="role" required>
						<option value="">Select your role</option>
						<option value="admin">Admin</option>
						<option value="judge">Judge</option>
					</select>
				</div>
			</div>

			<!-- Event ID (Hidden initially) -->
			<div class="event-section" id="eventSection">
				<div class="form-group">
					<label for="event_id" class="form-label">Event ID</label>
					<div class="input-icon">
						<i class="fas fa-calendar-check"></i>
						<input class="form-control" type="number" id="event_id" name="event_id" placeholder="Enter Event ID">
					</div>
					<div id="event_name" class="event-validation"></div>
				</div>
			</div>

			<!-- Submit Button -->
			<button class="btn-submit" type="submit" id="submitBtn">
				<i class="fas fa-paper-plane"></i>
				<span>Submit Request</span>
			</button>

			<!-- Login Link -->
			<div class="login-link">
				Already have an account? <a href="index.php">Sign in here</a>
			</div>
		</form>
	</div>
</div>

<script>
$(document).ready(function() {
	// Show/Hide Event field if Judge is selected
	$('#role').on('change', function() {
		if ($(this).val() === 'judge') {
			$('#eventSection').addClass('show');
			$('#event_id').attr('required', true);
		} else {
			$('#eventSection').removeClass('show');
			$('#event_id').val('').attr('required', false);
			$('#event_name').html('');
		}
	});

	// Validate Event ID via AJAX with debounce
	let eventIdTimeout;
	$('#event_id').on('input', function() {
		const eventId = $(this).val().trim();
		
		clearTimeout(eventIdTimeout);
		
		if (eventId === '') {
			$('#event_name').html('');
			return;
		}

		// Show loading state
		$('#event_name').html('<i class="fas fa-spinner fa-spin text-primary"></i> <span style="color: #667eea;">Validating...</span>');

		eventIdTimeout = setTimeout(function() {
			$.ajax({
				url: 'fetch_event_name.php',
				type: 'GET',
				data: { id: eventId },
				success: function(response) {
					if (response && response.trim() !== '') {
						$('#event_name').html('<i class="fas fa-check-circle text-success"></i> <span class="text-success">Event: ' + response + '</span>');
					} else {
						$('#event_name').html('<i class="fas fa-times-circle text-danger"></i> <span class="text-danger">Invalid Event ID</span>');
					}
				},
				error: function() {
					$('#event_name').html('<i class="fas fa-exclamation-triangle text-danger"></i> <span class="text-danger">Connection error</span>');
				}
			});
		}, 500); // 500ms debounce
	});

	// Form submission with loading state
	$('#requestForm').on('submit', function(e) {
		const submitBtn = $('#submitBtn');
		submitBtn.prop('disabled', true);
		submitBtn.html('<span class="loading-spinner"></span> <span>Processing...</span>');
	});
});
</script>

</body>
</html>