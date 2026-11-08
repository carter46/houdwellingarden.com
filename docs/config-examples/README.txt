Config templates for developers only — not required on the production server.

On the live host you should have:
  config.php          (required — copy from config.php.example at site root)
  config.local.php    (optional overrides / secrets)

Templates in this folder are for your PC or git clone setup. Do not upload this folder
to InfinityFree unless you want offline reference; it is safe to omit from deploy zips.
