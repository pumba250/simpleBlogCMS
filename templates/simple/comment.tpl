                    <div class="w3-panel w3-border w3-light-grey w3-padding" style="margin-bottom: 16px;">
                        <div class="w3-row">
                            <div class="w3-col m10">
                                <strong>{user_name}:</strong>
                                <p>{user_text}</p>
                            </div>
                            <div class="w3-col m2">
                                <div class="w3-right">
                                    <form method="post" action="?id={theme_id}" class="w3-bar-item">
                                        <input type="hidden" name="vote_comment" value="{id}_plus">
										<input type="hidden" name="csrf_token" value="{csrf_token}">
                                        <button type="submit" class="w3-button w3-small w3-green">
                                            <i class="fa fa-thumbs-up"></i> {comm_rating.plus}
                                        </button>
                                    </form>
                                    <form method="post" action="?id={theme_id}" class="w3-bar-item">
                                        <input type="hidden" name="vote_comment" value="{id}_minus">
										<input type="hidden" name="csrf_token" value="{csrf_token}">
                                        <button type="submit" class="w3-button w3-small w3-red" >
                                            <i class="fa fa-thumbs-down"></i> {comm_rating.minus}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>