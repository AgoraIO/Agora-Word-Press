<div class="buttons-top">
  <div class="left-button">
    <div class= "room-title">
      <?php if(isset($page_title)) { echo $page_title; } else { wp_title(); } ?> | <span id="count-users">1</span> <i id="count-users-icon" class="fas fa-users"></i>
    </div>
  </div>
  <div class="right-buttons">

    <button id="exit-btn" class= "leave-meeting btn-danger only-desktop other-buttons">
      <?php _e('Leave Meeting', 'agoraio'); ?>
    </button>
    <i class="icon-cog"></i>
  </div>
</div>