<?php
declare(strict_types=1);

require_once __DIR__ . '/../site-features.php';

if (!smartsupp_chat_enabled()) {
    return;
}
$key = smartsupp_chat_key();
?>
<script type="text/javascript">
var _smartsupp = _smartsupp || {};
_smartsupp.key = <?= json_encode($key, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.smartsupp||(function(d) {
  var s,c,o=smartsupp=function(){ o._.push(arguments)}; o._=[];
  s=d.getElementsByTagName('script')[0]; c=d.createElement('script');
  c.type='text/javascript'; c.charset='utf-8'; c.async=true;
  c.src='https://www.smartsuppchat.com/loader.js?'; s.parentNode.insertBefore(c,s);
})(document);
</script>
