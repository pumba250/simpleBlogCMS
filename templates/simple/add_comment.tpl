<div class="w3-container">
            <form method="post">
				<input type="hidden" name="csrf_token" value="{$csrf_token}">
                
                    <input class="w3-input w3-border" type="text" name="user_name" required placeholder="{l_name}"><br>
                
                <textarea class="w3-input w3-border" style="height: 80px;" name="user_text" required placeholder="{l_comment}"></textarea><br>
                <button class="w3-button w3-padding-large w3-white w3-border" type="submit"><b>{l_submit}</b></button>
            </form>
        </div>
	<hr>
	</div>