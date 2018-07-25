
<!DOCTYPE html>
<html lang="en">
  <head>
    <base href="https://localhost:8081">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="shortcut icon" href="images/favicon.ico">

    <title>Register a New Device</title>

    <!-- Bootstrap core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- pForms CSS -->
    <link rel="stylesheet" type="text/css" href="vendors/pforms/view.css" media="all">
    <script type="text/javascript" src="vendors/pforms/view.js"></script>

    <!-- Custom styles for this template -->
    <link href="css/dashboard.css" rel="stylesheet">
    <link href="vendors/nv/src/nv.d3.css" rel="stylesheet" type="text/css">
    <link href="vendors/datatables/jquery.dataTables.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="vendors/jquery-ui/css/theme/jquery-ui-1.10.4.custom.min.css">

    <style>
      svg {
        display: block;
      }

      #chart1 svg {
        height: 500px;
        min-width: 100px;
        min-height: 100px;
      /*
        margin: 50px;
        Minimum height and width is a good idea to prevent negative SVG dimensions...
        For example width should be =< margin.left + margin.right + 1,
        of course 1 pixel for the entire chart would not be very useful, BUT should not have errors
      */
      }

      #datatype_scale input {margin-bottom: 5px; margin-right: 20px;}
      #datatype_scale_values {width: 80%;}

      #datatype_multiplechoice input {margin-bottom: 5px;}
      #datatype_multiplechoice {width: 60%;}

    </style>

    <!-- Just for debugging purposes. Don't actually copy this line! -->
    <!--[if lt IE 9]><script src="../../assets/js/ie8-responsive-file-warning.js"></script><![endif]-->

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>

  <body>

    <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
      <div class="container-fluid">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#"><img src="images/title.png" name="Commodity Sensors Powered by the Crowd"/></a>
        </div>
        <div class="navbar-collapse collapse">
          <ul class="nav navbar-nav navbar-right">
          </ul>
        </div>
      </div>
    </div>

    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-3 col-md-2 sidebar">

          <ul class="nav nav-sidebar">
            <li class="active"><a href="#">Devices and Sensors</a></li>
          </ul>
          <?php foreach ($devices as $device): ?>
                  <ul class="nav nav-sidebar">
                  <li class="device"><a href="dashboard/device/<?=$device["properties"]->device_id?>"><?=$device["properties"]->model?> (<?=$device["properties"]->nickname?>)</a></li>
                    <ul>
                    <?php foreach ($device["sensors"] as $s): ?>
                      <li><a href="dashboard/sensor/<?=$s->device_id?>/<?=$s->sensor_id?>"><?=$s->sensor_name?></a></li>
                    <?php endforeach; ?>
                    </ul>
                  </ul>
          <?php endforeach; ?>

        </div>

        <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
 
               <div id="form_container">
              
                <h1 class="page-header"><a>Register a New Device</a></h1>
                <form id="form_sensor_settings" class="appnitro"  method="post" action="#">
                  <div class="form_description">
                    <div id="notifications">
                    </div>
                    <p>Use this form to register a new device. All fields are required.</p>
                  </div>            
                
                    <ul>
                      
                      <li id="li_1" >
                        <label class="description" for="element_1">Device Nickname </label>
                        <div>
                          <input id="element_1" name="nickname" class="element text medium" type="text" maxlength="255" value=""/> 
                          <p class="guidelines" id="guide_6"><small>Provide a name for this device.</small></p> 
                        </div> 
                      </li>
                      <li id="li_2" >
                        <label class="description" for="element_2">Unique Identifier</label>
                        <div>
                          <input id="element_2" name="device_id" class="element text large" type="text" maxlength="255" value=""/> 
                        </div>
                        <p class="guidelines" id="guide_2"><small>Please refer to the device serial number.</small></p> 
                      </li>
					  
                      <li id="li_3" >
                        <label class="description" for="element_2">Password</label>
                        <div>
                          <input id="element_2" name="password" class="element text large" type="text" maxlength="255" value=""/> 
                        </div>
                        <p class="guidelines" id="guide_2"><small>A security passphrase that the device will use for authentication purposes</small></p> 
                      </li>
                      
                      <li id="li_4" >
                        <label class="description" for="element_2">Model Number</label>
                        <div>
                          <input id="element_2" name="model" class="element text large" type="text" maxlength="255" value=""/> 
                        </div>
                        <p class="guidelines" id="guide_2"><small>Provide the device model number, if applicable</small></p> 
                      </li>
					  
                      <li id="li_5" >
                        <label class="description" for="element_2">Image Width</label>
                        <div>
                          <input id="element_2" name="image_width" class="element text large" type="text" maxlength="255" value=""/> 
                        </div>
                        <p class="guidelines" id="guide_2"><small>Camera Image Width</small></p> 
                      </li>
					  
                      <li id="li_6" >
                        <label class="description" for="element_2">Image Height</label>
                        <div>
                          <input id="element_2" name="image_height" class="element text large" type="text" maxlength="255" value=""/> 
                        </div>
                        <p class="guidelines" id="guide_2"><small>Camera Image Height</small></p> 
                      </li>
					  
                      <li id="li_7" >
                        <label class="description" for="element_2">Device Pixel Density</label>
                        <div>
                          <input id="element_2" name="display_density" class="element text large" type="text" maxlength="255" value=""/> 
                        </div>
                        <p class="guidelines" id="guide_2"><small>Enter 1.0 as default</small></p> 
                      </li>
					  
                      <li id="li_7" >
                        <label class="description" for="element_2">OS Version</label>
                        <div>
                          <input id="element_2" name="os_version" class="element text large" type="text" maxlength="255" value=""/> 
                        </div>
                        <p class="guidelines" id="guide_2"><small>The OS version running on the device</small></p> 
                      </li>
					  
                      
                  
                          <li class="buttons">
                          <input type="hidden" name="form_id" value="805949" />
                          <input id="saveForm" class="button_text" type="submit" name="submit" value="Register Device" onClick="javascript:updateSettings();"/>
                          </li>
                  </ul>
                </form> 
    </div>

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="js/jquery.min.js"></script>
    

    <script src="js/bootstrap.min.js"></script>
    <script src="js/docs.min.js"></script>


    <script src="vendors/nv/lib/d3.v3.js"></script>
    <script src="vendors/nv/nv.d3.js"></script>
    <script src="vendors/nv/src/tooltip.js"></script>
    <script src="vendors/nv/src/utils.js"></script>
    <script src="vendors/nv/src/models/legend.js"></script>
    <script src="vendors/nv/src/models/axis.js"></script>
    <script src="vendors/nv/src/models/scatter.js"></script>
    <script src="vendors/nv/src/models/line.js"></script>
    <script src="vendors/nv/src/models/lineWithFocusChart.js"></script>
    <script src="vendors/nv/stream_layers.js"></script>
    <script src="vendors/datatables/jquery.dataTables.js"></script>
    <script src="vendors/canvas/canvas.js"></script>

    <script src="vendors/jquery-ui/js/jquery-ui-1.10.4.custom.min.js"></script>
    <script>


    nv.addGraph(function() {
      var chart = nv.models.lineWithFocusChart();

     // chart.transitionDuration(500);
      chart.xAxis
          .tickFormat(d3.format(',f'));
      chart.x2Axis
          .tickFormat(d3.format(',f'));

      chart.yAxis
          .tickFormat(d3.format(',.2f'));
      chart.y2Axis
          .tickFormat(d3.format(',.2f'));

      d3.select('#chart svg')
          .datum(testData())
          .call(chart);

      nv.utils.windowResize(chart.update);

      return chart;
    });



    function testData() {
      return stream_layers(3,128,.1).map(function(data, i) {
        return { 
          key: 'Stream' + i,
          values: data
        };
      });
    }


    </script>

    <script>

      $(document).ready(function() {


      });

      
      function reIndexScaleValuePairs()
      {
          $('#datatype_scale_values p').each(function(index) {
                $(this).find('strong').html((index+1) + ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
                $(this).find('input').attr("name", "datatype_scale_option_"+index);
          });
      }

      function reIndexMultipleChoiceItems()
      {
          $('#datatype_multiplechoice_values p').each(function(index) {
                $(this).find('input').attr("name", "datatype_multiplechoice_choice_"+index);
          });
      }

      function anchorToDragHandle(element)
      {
          $(element).button({
              icons: {
                primary: "ui-icon-grip-dotted-horizontal"
              },
              text: false
            }).disableSelection();

            $(element).click(function(event) {
                 event.preventDefault();
            });
      }

      function anchorToScaleDeleteButton(element) {
          $(element).button({
            icons: {
              primary: "ui-icon-close"
            },
            text: false
          }).disableSelection();

          $(element).click(function(event) {
               event.preventDefault();
               var idx = $("#datatype_scale_values a.delete_button").index(element);
               if ($("#datatype_scale_values a.delete_button").length > 2 && idx>=0) {
                  $("#datatype_scale_values p").eq(idx).remove();
                  reIndexScaleValuePairs();
               }
          });
      }

      function anchorToMultipleChoiceDeleteButton(element) {
          $(element).button({
            icons: {
              primary: "ui-icon-close"
            },
            text: false
          }).disableSelection();

          $(element).click(function(event) {
               event.preventDefault();
               var idx = $("#datatype_multiplechoice_values a.delete_button").index(element);
               if ($("#datatype_multiplechoice_values a.delete_button").length > 2 && idx>=0) {
                  $("#datatype_multiplechoice_values p").eq(idx).remove();
                  reIndexMultipleChoiceItems();
               }
          });
      }

      function showDataTypeUI(d) 
      {
          $('li[id^=datatype_]').hide();

          if (d=="YESNO")
            $('li[id=datatype_yesno').show();
          if (d=="NUMBER")
            $('li[id=datatype_number').show();
          if (d=="SCALE")
            $('li[id=datatype_scale').show();
          if (d=="MULTIPLECHOICE")
            $('li[id=datatype_multiplechoice').show();
          if (d=="FREETEXT")
            $('li[id=datatype_freetext').show();
      }

      function loadItemsForDataType(d, values)
      {
        if (d=="YESNO") {
          $('li[id=datatype_yesno').show();
        }

        if (d=="NUMBER") {
          $('li[id=datatype_number').show();
          $('input[name="datatype_number_min"').val(values.minValue);
          $('input[name="datatype_number_max"').val(values.maxValue);
        }

        if (d=="SCALE") {
          $('li[id=datatype_scale').show();
          
          // Grab all scale values
          for (var i=0; i<values.length; i++) {
              if (i<2) {
                  $('input[name="datatype_scale_option_'+i+'"]').val(values[i].label);
              } else {
                   newScaleValuePair(i,values[i].label);
              }
          }


        }

        if (d=="MULTIPLECHOICE") {
          $('li[id=datatype_multiplechoice').show();

          // Grab all scale values
          for (var i=0; i<values.length; i++) {
              if (i<2) {
                  $('input[name="datatype_multiplechoice_choice_'+i+'"]').val(values[i]);
              } else {
                   newMultipleChoiceItem(i,values[i]);
              }
          }

        }

        if (d=="FREETEXT") {
          $('li[id=datatype_freetext').show();
        }
      }

      function newScaleValuePair(level,val)
      {
           var scaleItem = $('<p><strong>'+(level+1)+' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong><input name="datatype_scale_option_0" class="element text medium" type="text" maxlength="255" value="'+val+'"/>  <a href="#" class="drag_handle">Move</a><a href="#" class="delete_button">Delete</a></p>');
           $('#datatype_scale_values').append(scaleItem);
           reIndexScaleValuePairs();
           anchorToDragHandle($(scaleItem).find('a.drag_handle'));
           anchorToScaleDeleteButton($(scaleItem).find('a.delete_button'));
           
      }

      function newMultipleChoiceItem(indx, val) {
          var itm = $('<p><input name="datatype_multiplechoice_choice_'+indx+'" class="element text medium" type="text" maxlength="255" value="'+val+'"/> <a href="#" class="drag_handle">Move</a><a href="#" class="delete_button">Delete</a></p>');
          $('#datatype_multiplechoice_values').append(itm);
          reIndexMultipleChoiceItems();
          anchorToDragHandle($(itm).find('a.drag_handle'));
          anchorToMultipleChoiceDeleteButton($(itm).find('a.delete_button'));
      }

      function assembleDatatypeOptionsFromSelection(d)
      {
          // Process values
          result = {};

          if (d=="YESNO") {
              result.label = "YESNO";
          }

          if (d=="NUMBER") {
            result.label = "NUMBER";
            result.minValue = $('input[name="datatype_number_min"').val();
            result.maxValue = $('input[name="datatype_number_max"').val();
          }

          if (d=="SCALE") {
            // Grab all scale values
            var scaleValuePairs = [];
            for (var i=0; i<$("#datatype_scale_values p").length; i++) {
                var label = $("#datatype_scale_values p").eq(i).find('input').val();
                var value = $("#datatype_scale_values p").eq(i).find('strong').html().replace(' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', '');
                scaleValuePairs.push({"label":label, "value":value});
            }
            result = scaleValuePairs;
          }

          if (d=="MULTIPLECHOICE") {
            // Grab choices
            var choices = [];
            for (var i=0; i<$("#datatype_multiplechoice_values p").length; i++) {
                var choice = $("#datatype_multiplechoice_values p").eq(i).find('input').val();
                choices.push(choice);
            }
            result = choices;
          }

          if (d=="FREETEXT") {
            result.label = "FREETEXT";
          }

          return JSON.stringify(result);
      }

      function updateSettings()
      {
          
          /////////////////////////////////////////////////////////
          // Append Hidden Variables
          /////////////////////////////////////////////////////////
          $('#form_sensor_settings').append('<input type="hidden" name="area_id" value="1">');
		  $('#form_sensor_settings').append('<input type="hidden" name="online" value="true">');
          $('#form_sensor_settings').append('<input type="hidden" name="stealth_mode" value="false">');
		  
          // Submit
          $('#form_sensor_settings').attr('action', 'device/new_device/');
          $('#form_sensor_settings').append('<input type="hidden" name="settings_updated" value="submittedFromForm">');
          // $("#form_sensor_settings").submit();

      }

    </script>



  </body>
</html>
