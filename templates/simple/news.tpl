    <div class="w3-card-4 w3-margin w3-white">
        <div class="w3-container">
            <h3><b>{title}</b></h3>
            <h5><span class="w3-opacity">{created_at}</span></h5>
        </div>
        <div class="w3-container">
            <p>{!content}</p> 
            <div class="w3-panel w3-light-grey w3-padding">
                <p>{l_voteart}</p>
                <div class="w3-bar">
                    <form method="post" action="?id={id}" class="w3-bar-item">
						<input type="hidden" name="id" value="{id}">
                        <input type="hidden" name="vote_article" value="like">
						<input type="hidden" name="csrf_token" value="{csrf_token}">
                        <button type="submit" class="w3-button w3-green" {hasVotedArticle}>
                            <i class="fa fa-thumbs-up"></i> {article_rating.likes}
                        </button>
                    </form>
                    <form method="post" action="?id={id}" class="w3-bar-item">
						<input type="hidden" name="id" value="{id}">
                        <input type="hidden" name="vote_article" value="dislike">
						<input type="hidden" name="csrf_token" value="{csrf_token}">
                        <button type="submit" class="w3-button w3-red" {hasVotedArticle}>
                            <i class="fa fa-thumbs-down"></i> {article_rating.dislikes}
                        </button>
                    </form>
                </div>
            </div>
            <div class="w3-row">
                <div class="w3-col m8 s12">
                    <p><a href="?"><button class="w3-button w3-padding-large w3-white w3-border"><b>
                        {l_back} »
                    </b></button></a></p>
                </div>
            </div>
        </div>
    </div>
    <div class="w3-card-4 w3-margin w3-white">
        <div class="w3-container">
            <h3><b>{l_comments}</b></h3>
        </div>
       