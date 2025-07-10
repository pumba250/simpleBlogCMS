<div class="w3-card-4 w3-margin w3-white">
    <div class="w3-container w3-padding">
        <h3><b>{title}</b></h3>
        <h5><span class="w3-opacity">{created_at}</span></h5>
    </div>
    <div class="w3-container">
        <p>{!content}...{no_news_message}</p>
        <div class="w3-bar">
            <span class="w3-bar-item w3-small">
                <i class="fa fa-thumbs-up"></i> {article_rating.likes}
            </span>
            <span class="w3-bar-item w3-small">
                <i class="fa fa-thumbs-down"></i> {article_rating.dislikes}
            </span>
        </div>
        <div class="w3-row">
            <div class="w3-col m8 s12">
                <p><a href="?id={id}"><button class="w3-button w3-padding-large w3-white w3-border"><b>
				{l_read_more} »
                </b></button></a></p>
            </div>
        </div>
    </div>
</div>
<hr>