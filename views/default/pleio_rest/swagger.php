<?php
    $root = "/mod/pleio_rest/vendor/swagger-ui/";
    $api_path = "/api/doc/swagger";
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Pleio REST API - Swagger</title>
  <link rel="icon" type="image/png" href="<?php echo $root; ?>images/favicon-32x32.png" sizes="32x32" />
  <link rel="icon" type="image/png" href="<?php echo $root; ?>images/favicon-16x16.png" sizes="16x16" />
  <link href='<?php echo $root; ?>css/typography.css' media='screen' rel='stylesheet' type='text/css'/>
  <link href='<?php echo $root; ?>css/reset.css' media='screen' rel='stylesheet' type='text/css'/>
  <link href='<?php echo $root; ?>css/screen.css' media='screen' rel='stylesheet' type='text/css'/>
  <link href='<?php echo $root; ?>css/reset.css' media='print' rel='stylesheet' type='text/css'/>
  <link href='<?php echo $root; ?>css/print.css' media='print' rel='stylesheet' type='text/css'/>
  <script src='<?php echo $root; ?>lib/jquery-1.8.0.min.js' type='text/javascript'></script>
  <script src='<?php echo $root; ?>lib/jquery.slideto.min.js' type='text/javascript'></script>
  <script src='<?php echo $root; ?>lib/jquery.wiggle.min.js' type='text/javascript'></script>
  <script src='<?php echo $root; ?>lib/jquery.ba-bbq.min.js' type='text/javascript'></script>
  <script src='<?php echo $root; ?>lib/handlebars-2.0.0.js' type='text/javascript'></script>
  <script src='<?php echo $root; ?>lib/underscore-min.js' type='text/javascript'></script>
  <script src='<?php echo $root; ?>lib/backbone-min.js' type='text/javascript'></script>
  <script src='<?php echo $root; ?>swagger-ui.js' type='text/javascript'></script>
  <script src='<?php echo $root; ?>lib/highlight.7.3.pack.js' type='text/javascript'></script>
  <script src='<?php echo $root; ?>lib/jsoneditor.min.js' type='text/javascript'></script>
  <script src='<?php echo $root; ?>lib/marked.js' type='text/javascript'></script>
  <script src='<?php echo $root; ?>lib/swagger-oauth.js' type='text/javascript'></script>

  <!-- Some basic translations -->
  <!-- <script src='<?php echo $root; ?>lang/translator.js' type='text/javascript'></script> -->
  <!-- <script src='<?php echo $root; ?>lang/ru.js' type='text/javascript'></script> -->
  <!-- <script src='<?php echo $root; ?>lang/en.js' type='text/javascript'></script> -->

  <script type="text/javascript">
    $(function () {
      var url = window.location.search.match(/url=([^&]+)/);
      if (url && url.length > 1) {
        url = decodeURIComponent(url[1]);
      } else {
        url = "<?php echo $api_path; ?>";
      }

      // Pre load translate...
      if(window.SwaggerTranslator) {
        window.SwaggerTranslator.translate();
      }
      window.swaggerUi = new SwaggerUi({
        url: url,
        dom_id: "swagger-ui-container",
        supportedSubmitMethods: ['get', 'post', 'put', 'delete', 'patch'],
        onComplete: function(swaggerApi, swaggerUi){
          if(typeof initOAuth == "function") {
            initOAuth({
              clientId: "your-client-id",
              clientSecret: "your-client-secret-if-required",
              realm: "your-realms",
              appName: "your-app-name",
              scopeSeparator: ",",
              additionalQueryStringParams: {}
            });
          }

          if(window.SwaggerTranslator) {
            window.SwaggerTranslator.translate();
          }

          $('pre code').each(function(i, e) {
            hljs.highlightBlock(e)
          });

          addApiKeyAuthorization();
        },
        onFailure: function(data) {
          log("Unable to Load SwaggerUI");
        },
        docExpansion: "none",
        jsonEditor: false,
        apisSorter: "alpha",
        defaultModelRendering: 'schema',
        showRequestHeaders: false
      });

      function addApiKeyAuthorization(){
        var key = encodeURIComponent($('#input_apiKey')[0].value);
        if(key && key.trim() != "") {
            var apiKeyAuth = new SwaggerClient.ApiKeyAuthorization("api_key", key, "query");
            window.swaggerUi.api.clientAuthorizations.add("api_key", apiKeyAuth);
            log("added key " + key);
        }
      }

      $('#input_apiKey').change(addApiKeyAuthorization);

      // if you have an apiKey you would like to pre-populate on the page for demonstration purposes...
      /*
        var apiKey = "myApiKeyXXXX123456789";
        $('#input_apiKey').val(apiKey);
      */

      window.swaggerUi.load();

      function log() {
        if ('console' in window) {
          console.log.apply(console, arguments);
        }
      }
  });
  </script>
</head>

<body class="swagger-section">
<div id='header'>
  <div class="swagger-ui-wrap">
    <a id="logo" href="http://swagger.io">swagger</a>
    <form id='api_selector'>
      <div class='input'><input placeholder="http://example.com/api" id="input_baseUrl" name="baseUrl" type="text"/></div>
      <div class='input'><input placeholder="api_key" id="input_apiKey" name="apiKey" type="text"/></div>
      <div class='input'><a id="explore" href="#" data-sw-translate>Explore</a></div>
    </form>
  </div>
</div>

<div id="message-bar" class="swagger-ui-wrap" data-sw-translate>&nbsp;</div>
<div id="swagger-ui-container" class="swagger-ui-wrap"></div>
</body>
</html>