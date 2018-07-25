
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

    <title><?=ucwords(str_replace("?","",$sensor->sensor_question))."?"?></title>

    <!-- Bootstrap core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/dashboard.css" rel="stylesheet">
    <link href="vendors/nv/src/nv.d3.css" rel="stylesheet" type="text/css">
    <link href="vendors/datatables/jquery.dataTables.css" rel="stylesheet" type="text/css">
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
            <li><a href="#">Dashboard</a></li>
            <li><a href="#">Settings</a></li>
            <li><a href="#">Profile</a></li>
            <li><a href="#">Help</a></li>
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
                  <li><a href="dashboard/device/<?=$device["properties"]->device_id?>"><?=$device["properties"]->model?> (<?=$device["properties"]->nickname?>)</a></li>
                    <ul>
                    <?php foreach ($device["sensors"] as $s): ?>
                      <li><a href="dashboard/sensor/<?=$s->device_id?>/<?=$s->sensor_id?>"><?=$s->sensor_name?></a></li>
                    <?php endforeach; ?>
                    </ul>
                  </ul>
          <?php endforeach; ?>

        </div>

        <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
          <h1 class="page-header"><?=ucwords(str_replace("?","",$sensor->sensor_question))."?"?></h1>
          <p><a href="sensor/settings/<?=$sensor->device_id?>/<?=$sensor->sensor_id?>">Modify Sensor Settings</a></p>
          <div id="chart" class='with-3d-shadow with-transitions'>
              <svg style="height: 500px;"></svg>
          </div>

           <div class="table-responsive">
            <table class="table table-striped" id="datastreams">
              <thead>
                <tr>
                  <th>Sensor</th>
                  <th>Device</th>
                  <th>Datapoint</th>
                  <th>Answer</th>
                  <th>Number of Votes</th>
                </tr>
              </thead>
              <tbody>

                <?php foreach ($datapoints as $datapoint): ?>
                  <tr>
                    <td><?=$datapoint->sensor_id?></td>
                    <td><?=$devices[$datapoint->device_id]["properties"]->model?></td>
                    <td><a href="datastream/image/<?=$datapoint->device_id?>/<?=$datapoint->sensor_id?>/<?=$datapoint->datapoint?>" target="_blank"><?=date('D M d Y H:ia',intval($datapoint->datapoint)/1000)?></a></td>
                    <td></td>
                    <td><?=$datapoint->number_votes?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

        </div>

        </div>  
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
          var oTable = $('#datastreams').dataTable();
          // Sort immediately with column 2 (at position 1 in the array (base 0). More could be sorted with additional array elements
          oTable.fnSort( [ [2,'desc'] ] );
      });

    </script>



  </body>
</html>
