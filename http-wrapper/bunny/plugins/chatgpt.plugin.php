<?php
if(isset($_POST['question']) && trim($_POST['question']) != "")
{
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/chatgpt/ask?question=".urlencode($_POST['question'])."&".$ojnAPI->getToken()));
	header("Location: bunny_plugin.php?p=chatgpt");
	exit();
}
?>

<div class="card">
  <h6 class="card-header bg-success text-white"><?php echo __tr("Ask ChatGPT") ?></h6>
  <div class="card-body">
    <form method="post">
      <div class="form-group row">
        <label class="col-sm-2 col-form-label" for="question"><?php echo __tr("Question") ?></label>
        <div class="col-sm-8">    
          <input type="text" name="question" class="form-control" placeholder="<?php echo __tr("What's the weather like?") ?>" />
          <small class="form-text text-muted"><?php echo __tr("Ask any question and your bunny will speak the ChatGPT response") ?></small>
        </div>
        <div class="col-sm-2">
          <button class="btn btn-success" type="submit"><?php echo __tr("Ask") ?></button>
        </div>
      </div>
    </form>
  </div>
</div>