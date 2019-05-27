<ul class="news-items">
<?php
if(!apcu_fetch(APC_PREFIX.'ojn_news_'.$Infos['language'])) {
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}

	$sql = "SELECT news.*, news_translation.title as tr_title, news_translation.content as tr_content FROM news LEFT JOIN news_translation ON news_translation.news=news.id AND news_translation.language='".$Infos['language']."' WHERE status=1 OR status=4 ORDER BY status DESC, date DESC;";
	$res = mysqli_query($link, $sql);
	while($row = mysqli_fetch_assoc($res))
	{
		$news[] = $row;
	}

	mysqli_close($link);
	apcu_store(APC_PREFIX.'ojn_news_'.$Infos['language'], $news, 3600);
}
else
	$news = apcu_fetch(APC_PREFIX.'ojn_news_'.$Infos['language']);
$c = 0;
foreach($news as $new) {
	if($c < 3) {
?>
    <li>
        <div class="news-item-detail">	
            <a class="news-item-title">
		<?php if(time() - strtotime($new['date']) < 3600 * 24 * 2) { ?><span class="label label-info"><?php echo __tr("New") ?></span> <?php } ?>
		<?php if($new['status'] == 4) { ?><span class="label label-info"><?php echo __tr("Important") ?></span> <?php } ?>
		<?php echo strlen($new['tr_title']) ? $new['tr_title'] : $new['title'] ?>
	    </a>
            <p class="news-item-preview"><?php echo strlen($new['tr_content']) ? $new['tr_content'] : $new['content'] ?></p>
        </div>
        <div class="news-item-date">
            <span class="news-item-day"><?php echo str_pad(date("d", strtotime($new['date'])), 2, "0", STR_PAD_LEFT) ?></span>
            <span class="news-item-month"><?php echo str_pad(date("M", strtotime($new['date'])), 2, "0", STR_PAD_LEFT) ?></span>
        </div>
    </li>
<?php
	}
	$c++;
}
?>
</ul>
