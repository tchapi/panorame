<div id="header">
  <!-- Logo -->
  <div class="pull-left" id="logo">
    <h1><a href="/"><?php echo strtoupper(_name); ?></a></h1>
  </div>

  <!-- Pages -->
  <div id="pages" class="pull-right">
    <ul>
<?php foreach($constants['pages'] as $page): ?>
      <li class="item <?php echo $page['slug']; ?><?php if ($parameters['page']['slug'] == $page['slug']) echo ' active'; ?>"><a href="<?php echo (isset($page['home'])&&$page['home']===true)?"/":"/page/".$page['slug']; ?>"><?php echo $page['name']; ?></a></li>
<?php endforeach; ?>
    </ul>
  </div>
</div>
<div id="corner">
  <select id="lang" class="pull-right hide">
    <option value="FR">Français</option>
    <option value="US">English</option>
    <option value="ES">Español</option>
    <option value="DE">Deutsch</option>
  </select>
</div>
