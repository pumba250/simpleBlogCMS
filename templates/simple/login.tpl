<div class="w3-card-4 w3-margin w3-white">
    <div class="w3-container w3-center w3-padding-32">
        <h2 class="w3-wide">{loginTitle}</h2>
        {errorMessage}
        <form method="POST" class="auth-form">
            {formFields}
            <button type="submit" class="w3-button w3-dark-grey">{submitButtonText}</button>
        </form>
        <div class="auth-links">
            {authLinks}
        </div>
    </div>
</div>