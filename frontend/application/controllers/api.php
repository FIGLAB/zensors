<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Api extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -  
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in 
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
    
	public $allowed_origins = ["http://localhost:8081"];
	public function index()
	{
		
        $this->load->database();

	}

	public function deviceExists()
	{
        // Load the Database Driver
        $this->load->database();
        $device_id = $this->input->get_post('device_id');
        $query = $this->db->get_where('devices', array('device_id'=>$device_id));
		$exists = ($query->num_rows()>0);

		$this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($exists));
	}
	
	public function zensorsForDevice()
	{
        // Load the Database Driver
        $this->load->database();
        $device_id = $this->input->get_post('device_id');
        $query = $this->db->get_where('sensors', array('device_id'=>$device_id));

        // Set Header to JSON
        $results = array();
        foreach ($query->result() as $row)
        {
            unset($row->data);
            array_push($results,$row);
        }

        // Return JSON Value
		$origin = $this->input->get_request_header('Origin', TRUE);
		if (in_array($origin, $this->allowed_origins)) {
			$this->output->set_header("Access-Control-Allow-Origin: {$origin}");
		}
		$this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($results));
	}

    public function deployedSensors()
    {
        // Load the Database Driver
        $this->load->database();
        $deployment_id = $this->input->get_post('deployment_id');
        $this->db->where(array('deployment_id'=>$deployment_id));
        $query = $this->db->get('sensors');

        // Set Header to JSON
        $results = array();
        foreach ($query->result() as $row)
        {
            unset($row->sensor_subwindowpoints);
            unset($row->data);
            array_push($results,$row);
        }

        // Return JSON Value
		$origin = $this->input->get_request_header('Origin', TRUE);
		if (in_array($origin, $this->allowed_origins)) {
			$this->output->set_header("Access-Control-Allow-Origin: {$origin}");
		}
		$this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($results));

    }
	
    public function registerHandler()
    {
        // Load the Database Driver
        $this->load->database();
		date_default_timezone_set('America/New_York');
        $endpoint = $this->input->get_post('endpoint');
		$ip = $this->input->get_post('ip');
		$port = $this->input->get_post('port');
		$last_seen = Date("Y-m-d H:i:s");
		
        $data = array('endpoint' => $endpoint, 'ip' => $ip, 'port' => $port, 'last_seen' => $last_seen);
        $this->db->where('endpoint', $endpoint);
        if ($this->db->update('backends', $data)) {
			$this->output
	                ->set_content_type('application/json')
	                ->set_output(json_encode(array('status'=>'success')));
        } else {
			$this->output
	                ->set_content_type('application/json')
	                ->set_output(json_encode(array('status'=>'failed')));
        }
    }
	
    public function activeSensors()
    {
        // Load the Database Driver
        $this->load->database();

        $this->db->where(array('active'=>'yes'));
        $query = $this->db->get('sensors');

        // Set Header to JSON
        $results = array();
        foreach ($query->result() as $row)
        {
            unset($row->sensor_subwindowpoints);
            unset($row->data);
            array_push($results,$row);
        }

        // Return JSON Value
        $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($results));

    }

    public function lastModified()
    {
        $this->load->database();
        $device_id = $this->input->get('device_id');

        $q = $this->db->get_where('devices', array('device_id' => $device_id));
        $stealth_mode = "";
        if ($q->num_rows()>0) {
            $stealth_mode = $q->row()->stealth_mode;
        }
        
        $query = $this->db->get_where('sensors', array('device_id' => $device_id));

        // Set Header to JSON
        $sensors = array();
        foreach ($query->result() as $row)
        {
            unset($row->sensor_subwindowpoints);
            unset($row->data);
            array_push($sensors,array("sensor_id" => $row->sensor_id, "last_updated" => $row->last_updated, "stealth_mode" => $stealth_mode));
        }

        // Save pulse to the database
        $this->db->where('device_id', $device_id);
        $this->db->update('devices', array('last_pulse' => time()*1000)); 

        // Return JSON Value
        $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($sensors));

    }

    public function unansweredSensorReadings()
    {
        // Input:
        // SensorID (optional): sensorID string
        // DeviceID (optional): deviceID string
        // MinimumVotes (optional): default = 0
        // Date (optional)

        // Load the Database Driver
        $this->load->database();
		
		// Allow-Origin: Localhost
		$origin = $this->input->get_request_header('Origin', TRUE);
		if (in_array($origin, $this->allowed_origins)) {
			$this->output->set_header("Access-Control-Allow-Origin: {$origin}");
		}
        // Query Active Sensors

        // Grab Parameters
        $min_votes = $this->input->get('min_votes');
        $time_bound = $this->input->get('since');
        $sensor_id = $this->input->get('sensor_id');
        $device_id = $this->input->get('device_id');
        $filter_inactive_sensors = $this->input->get('filter_inactive_sensors');
        $limit = $this->input->get('limit');
		$unanswered_by = $this->input->get('unanswered_by');
		
		$time_from = $this->input->get('from');
		$time_to = $this->input->get('to');
		
        // Assemble the Query 
        $where = array();

        if ($sensor_id)
            $where['sensor_id ='] = $sensor_id;

        if ($device_id)
            $where['device_id ='] = $device_id;

        if ($min_votes!=false)
            $where['number_votes <'] = $min_votes;
        else
            $where['number_votes ='] = 0;

        if ($time_bound!=false)
            $where['datapoint >='] = intval($time_bound); // multiply by 100 to convert ms to seconds
			
		if ($time_from!=false && $time_to!=false) {
			$where['datapoint >='] = intval($time_from);
			$where['datapoint <='] = intval($time_to);
		}
		
        // Filter for Active Sensors
        $active_sensors = array();
        $filter = false;
        if (strtolower($filter_inactive_sensors)=="yes" || strtolower($filter_inactive_sensors)=="true")
            $filter = true;

        if ($filter) {
            $this->db->where(array('active'=>'yes'));
            $query = $this->db->get('sensors');

            // Set Header to JSON
            foreach ($query->result() as $row)
            {
                unset($row->sensor_subwindowpoints);
                unset($row->data);
                array_push($active_sensors,$row->sensor_id);
            }

        }

        // Execute Query
		if ($unanswered_by!=false) {
			// Perform complex query!
			$where_complex = array();
			foreach ($where as $field => $val) {
				array_push($where_complex, $field."'".$val."'");
			}
			// Run a select statement to grab all votes by voter_id=$unanswered_by
			$this->db->select('datapoint');
            $this->db->where(array('voter_id'=>$unanswered_by));
            $query = $this->db->get('votes');
			if ($query->num_rows()>0) {
				$dps = array();
	            foreach ($query->result() as $row)
	            {
					array_push($dps,"'".$row->datapoint."'");
	            }
				array_push($where_complex, "datapoint not in (".implode(",",$dps).")");
			}            
			$where = implode(" AND ",$where_complex);
		}
		
        $this->db->where($where); 
		$this->db->order_by('number_votes', 'desc');
        $this->db->order_by('sensor_id', 'asc');
		$this->db->order_by('datapoint', 'asc');
            
        if ($limit) {
			$this->db->limit($limit);
            $query = $this->db->get('datastreams');
		} else {
            $query = $this->db->get('datastreams');
		}

        // Set Header to JSON
        $results = array();
        foreach ($query->result() as $row)
        {
            $row->image_url = "https://localhost:8081/datastream/image/".$row->device_id."/".$row->sensor_id."/".$row->datapoint;
            if ($filter) {
                if (in_array($row->sensor_id,$active_sensors))
                    array_push($results,$row);
            } else {
				array_push($results,$row);
            }
        }

        // Return JSON Value
        $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($results));

    }
		
    public function responsesForReading()
    {
        // Input:
        // SensorID (optional): sensorID string
        // DeviceID (optional): deviceID string
        // MinimumVotes (optional): default = 0
        // Date (optional)

        // Load the Database Driver
        $this->load->database();

        // Grab Parameters
        $datapoint = $this->input->get('datapoint');
        $sensor_id = $this->input->get('sensor_id');
        $device_id = $this->input->get('device_id');
        $session_id = $this->input->get('session_id');

        if (!$datapoint || !$sensor_id || !$device_id) {
            $error = "";
            if (!$datapoint)
                $error .= " Missing datapoint argument.";

            if (!$sensor_id)
                $error .= " Missing sensor_id argument.";

            if (!$device_id)
                $error .= " Missing device_id argument.";

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode("Error:".$error));
            return;
        }

        // Assemble the Query 
        $where = array();
        $where['sensor_id'] = $sensor_id;
        $where['device_id'] = $device_id;
        $where['datapoint'] = $datapoint;

        if ($session_id)
            $where['session_id'] = $session_id;
        
        // Execute Query
        $this->db->where($where); 
        $query = $this->db->get('votes');

        // Set Header to JSON
        $results = array();
        foreach ($query->result() as $row)
        {
            array_push($results,$row);
        }

        // Return JSON Value
        $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($results));

    }
	
    public function responsesForSensor()
    {
        // Input:
        // SensorID (optional): sensorID string
        // DeviceID (optional): deviceID string
        // MinimumVotes (optional): default = 0
        // Date (optional)

        // Load the Database Driver
        $this->load->database();

        // Grab Parameters
        $sensor_id = $this->input->get('sensor_id');
        $device_id = $this->input->get('device_id');
        $session_id = $this->input->get('session_id');

        if (!$sensor_id || !$device_id) {
            $error = "";
			
            if (!$sensor_id)
                $error .= " Missing sensor_id argument.";

            if (!$device_id)
                $error .= " Missing device_id argument.";

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode("Error:".$error));
            return;
        }

        // Assemble the Query 
        $where = array();
        $where['sensor_id'] = $sensor_id;
        $where['device_id'] = $device_id;

        if ($session_id)
            $where['session_id'] = $session_id;
        
        // Execute Query
        $this->db->where($where); 
		$this->db->order_by("datapoint","asc"); 
        $query = $this->db->get('votes');

        // Set Header to JSON
        $results = array();
        foreach ($query->result() as $row)
        {
            array_push($results,$row);
        }

        // Return JSON Value
        $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($results));

    }
	
    public function sensorStatus()
    {
        // Input:
        // SensorID (optional): sensorID string
        // DeviceID (optional): deviceID string
        // MinimumVotes (optional): default = 0
        // Date (optional)

        // Load the Database Driver
        $this->load->database();
		
		// Allow-Origin: Localhost
		$origin = $this->input->get_request_header('Origin', TRUE);
		if (in_array($origin, $this->allowed_origins)) {
			$this->output->set_header("Access-Control-Allow-Origin: {$origin}");
		}
        // Query Active Sensors

        // Grab Parameters
		$SENSORS_STATUS = array();
		$query = $this->db->query("SELECT sensor_id, device_id, count(datapoint) as num_datapoints FROM datastreams GROUP BY sensor_id ORDER BY sensor_id");
        foreach ($query->result() as $row)
        {
            $keypath = $row->device_id . "/" . $row->sensor_id;
			$SENSOR_STATUS[$keypath] = array('device_id'=>$row->device_id, 'sensor_id'=>$row->sensor_id, 'num_datapoints' => $row->num_datapoints, 'num_votes' => 0);
        }
		
		$query = $this->db->query("SELECT sensor_id, device_id, count(*) as vote_count FROM votes GROUP BY sensor_id ORDER BY sensor_id");
        foreach ($query->result() as $row)
        {
            $keypath = $row->device_id . "/" . $row->sensor_id;
			$SENSOR_STATUS[$keypath]['num_votes'] = $row->vote_count;
		}
		$results = array();
		foreach ($SENSOR_STATUS as $key => $val)
		{
			array_push($results,$SENSOR_STATUS[$key]);
		}

        // Return JSON Value
        $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($results));

    }
	
	//
	// public function cleanup()
	// {
	// 	$this->load->database();
	// 	$query = $this->db->query("SELECT device_id, sensor_id, datapoint, voter_id, count(datapoint) as count FROM votes GROUP BY datapoint, voter_id ORDER BY count desc");
	//
	//         $results = array();
	//         foreach ($query->result() as $row)
	//         {
	//             if ($row->count > 1)
	// 			array_push($results,$row);
	//         }
	//
	// 	// For all workers who voted more than once, delete every answere except the earliest one
	// 	foreach ($results as $row)
	// 	{
	// 		$query = $this->db->query("SELECT id, device_id, sensor_id, datapoint, voter_id FROM votes WHERE device_id='{$row->device_id}' AND sensor_id='{$row->sensor_id}' AND datapoint='{$row->datapoint}' AND voter_id='{$row->voter_id}' ORDER BY id ASC");
	//         $first_vote_id = $query->result()[0]->id;
	// 		$query = $this->db->query("DELETE FROM votes WHERE device_id='{$row->device_id}' AND sensor_id='{$row->sensor_id}' AND datapoint='{$row->datapoint}' AND voter_id='{$row->voter_id}' AND id<>'{$first_vote_id}'");
	// 	}
	//
	// 	$counts = array();
	// 	foreach ($results as $row)
	// 	{
	// 		$query = $this->db->query("SELECT device_id, sensor_id, datapoint, voter_id FROM votes WHERE device_id='{$row->device_id}' AND sensor_id='{$row->sensor_id}' AND datapoint='{$row->datapoint}' GROUP BY voter_id");
	//         $query = $this->db->query("UPDATE datastreams SET number_votes='{$query->num_rows}' WHERE device_id='{$row->device_id}' AND sensor_id='{$row->sensor_id}' AND datapoint='{$row->datapoint}' ");
	// 		/*
	// 		$voters = array();
	// 		foreach ($query->result() as $rr)
	//         {
	//             array_push($voters,$rr->voter_id);
	//         }
	// 		array_push($counts, array('device_id'=>$row->device_id, 'sensor_id'=>$row->sensor_id, 'datapoint'=>$row->datapoint, 'votes'=>$query->num_rows, 'voters'=>$voters));
	// 		*/
	// 	}
	//
	//
	// 	// Delete all redundant answers, but sync-up the vote count from the datapoint_db
	//
	//         // Return JSON Value
	//         // $this->output
	//         //        ->set_content_type('application/json')
	//         //        ->set_output(json_encode("Success!"));
	//
	// }

	
    public function responsesForSession()
    {
        // Input:
        // SensorID (optional): sensorID string
        // DeviceID (optional): deviceID string
        // MinimumVotes (optional): default = 0
        // Date (optional)

        // Load the Database Driver
        $this->load->database();

        // Grab Parameters
        $session_id = $this->input->get('session_id');

        if (!$session_id) {
            $error = "Missing session_id argument";
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode("Error:".$error));
            return;
        }

        // Assemble the Query 
        $where = array();
        $where['session_id'] = $session_id;
        
        // Execute Query
        $this->db->where($where); 
		$this->db->order_by("datapoint", "asc");
        $query = $this->db->get('votes');

        // Set Header to JSON
        $results = array();
        foreach ($query->result() as $row)
        {
            array_push($results,$row);
        }

        // Return JSON Value
        $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($results));

    }
	
    public function labelsForSensor()
    {
        // Input:
        // SensorID (optional): sensorID string
        // DeviceID (optional): deviceID string
        // MinimumVotes (optional): default = 0
        // Date (optional)

        // Load the Database Driver
        $this->load->database();

        // Grab Parameters
        $sensor_id = $this->input->get('sensor_id');
        $device_id = $this->input->get('device_id');
        $session_id = $this->input->get('session_id');

        if (!$sensor_id || !$device_id) {
            $error = "";
            if (!$sensor_id)
                $error .= " Missing sensor_id argument.";

            if (!$device_id)
                $error .= " Missing device_id argument.";

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode("Error:".$error));
            return;
        }

        // Assemble the Query 
        $where = array();
        $where['sensor_id'] = $sensor_id;
        $where['device_id'] = $device_id;

        if ($session_id)
            $where['session_id'] = $session_id;
        
        // Execute Query
        $this->db->where($where); 
        $query = $this->db->get('votes');

        // Set Header to JSON
        $results = array();
        foreach ($query->result() as $row)
        {
            array_push($results,$row);
        }

        // Return JSON Value
        $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($results));

    }
	

    public function votesForSession()
    {
        // Load the Database Driver
        $this->load->database();

        // Grab Form Input
        $session_id = $this->input->get('session_id');

        if(!$session_id) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode("Error: session_id argument is required."));
        }

        // Execute Query
        $this->db->where(array('session_id' => $session_id)); 
        $query = $this->db->get('votes');

        // Set Header to JSON
        $results = array();
        foreach ($query->result() as $row)
        {
            array_push($results,$row);
        }

        // Return JSON Value
        $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($results));

    }
	
	public function randomPointsFromSensorReadings()
	{
        // Load the Database Driver
        $this->load->database();
		$num_points = $this->input->get('num_points');
		if (!$num_points || !intval($num_points)) {
			$num_points = 25;
		} else {
			$num_points = intval($num_points);
		}
		

        // Grab Parameters
		$query = $this->db->query("SELECT * FROM sensors");
        $results = array();
        foreach ($query->result() as $row)
        {
            $qq = $this->db->query("SELECT * FROM datastreams WHERE device_id='{$row->device_id}' AND sensor_id='{$row->sensor_id}'");
			// Grab random Points
			$dps = array();
			$randDatapoints = array_rand($qq->result(),$num_points);
			for ($i=0; $i<$num_points; $i++) {
				array_push($dps, array('datapoint'=>$qq->result()[$randDatapoints[$i]]->datapoint,'view_url'=>"http://localhost:8081/zensorsLabeller/serial_viewer.html?device_id={$row->device_id}&sensor_id={$row->sensor_id}&datapoint={$qq->result()[$randDatapoints[$i]]->datapoint}")); 
			}
			array_push($results,array('device_id'=>$row->device_id, 'sensor_id'=>$row->sensor_id, 'random_datapoints'=>$dps));
        }

        // Return JSON Value
        $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($results));
	}

    public function newResponseForReading()
    {
        // Input:
        // SensorID (optional): sensorID string
        // DeviceID (optional): deviceID string
        // MinimumVotes (optional): default = 0
        // Date (optional)

        // Load the Database Driver
        $this->load->database();
		
		// Set Access-Control for local labelling
		$origin = $this->input->get_request_header('Origin', TRUE);
		if (in_array($origin, $this->allowed_origins)) {
			$this->output->set_header("Access-Control-Allow-Origin: {$origin}");
		}
		
        // Grab Parameters
        $datapoint = $this->input->get('datapoint');
        $sensor_id = $this->input->get('sensor_id');
        $device_id = $this->input->get('device_id');
        $session_id = $this->input->get('session_id');
        $answer = $this->input->get('answer');
        $response_time = $this->input->get('response_time');
        $flag = $this->input->get('flag');
        $flag_data = $this->input->get('flag_data');
		$voter_info = $this->input->get('voter_info');
		$vote_id = $this->input->get('vote_id');
		$voter_id = $this->input->get('voter_id');
		$do_not_increment = $this->input->get('do_not_increment');
		
        if (!$datapoint || !$sensor_id || !$device_id || $answer===false || !$response_time) {
            $error = "";
            if (!$datapoint)
                $error .= " Missing datapoint argument.";

            if (!$sensor_id)
                $error .= " Missing sensor_id argument.";

            if (!$device_id)
                $error .= " Missing device_id argument.";

            if ($answer===false)
                $error .= " Missing 'answer' argument.";

            if (!$response_time)
                $error .= " Missing 'response_time' argument.";

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode("Error:".$error));
            return;
        }

        // Check if Sensor ID and Device ID exists
        $fields = array('device_id'=>$device_id, 'sensor_id'=>$sensor_id, 'datapoint'=>$datapoint);
        $query = $this->db->get_where('datastreams',$fields);
        if ($query->num_rows() == 0) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode("Error: The datapoint ({$datapoint}), sensor ({$sensor_id}) or device ID ({$device_id}) does not exist."));
            return;
        }
		$datapoint_row = $query->row();
		
		// Check if a voter has already voted for a particular sensor reading...
		if ($voter_id) {
	        $fields = array('device_id'=>$device_id, 'sensor_id'=>$sensor_id, 'datapoint'=>$datapoint, 'voter_id'=>$voter_id);
	        $query = $this->db->get_where('votes',$fields);
	        if ($query->num_rows() != 0) {
	            $this->output
	                ->set_content_type('application/json')
	                ->set_output(json_encode("Error: voter_id='".$voter_id."' has already cast a vote for this datapoint ({$datapoint}), sensor ({$sensor_id}), device ID ({$device_id})."));
	            return;
	        }
		}
        
        // Assemble the data 
        $data = array();
        $data['sensor_id'] = $sensor_id;
        $data['device_id'] = $device_id;
        $data['datapoint'] = $datapoint;
        $data['answer'] = $answer;
        $data['response_time'] = $response_time;
		
        // Optional Arguments
        if ($flag)
            $data['flag'] = $flag;

        if ($flag_data)
            $data['flag_data'] = $flag_data;

        if ($session_id)
            $data['session_id'] = $session_id;
		
		if ($voter_info)
			$data['voter_info'] = $voter_info;
		
		if ($voter_id)
			$data['voter_id'] = $voter_id;
		
		$increment_vote_count = true;
		if ($do_not_increment) {
			if (strtolower($do_not_increment)=="yes" || strtolower($do_not_increment)=="true")
				$increment_vote_count = false;
		}
			
		
		// Check for presence of VOTE ID
		// If vote_id is not present, add a new vote entry
		// Otherwise, update entry from the provided vote_id
		$insert = true;
		if ($vote_id) {
			// Check if vote_id exists in the DB
			$query = $this->db->get_where('votes', array('id' => $vote_id));
			if ($query->num_rows()>0) { // ID exists
	        	$insert = false;
	        }
		}
		
		if ($insert) {
	        // Execute Query, Wrap it in a transaction to avoid concurrent DB writes
			$this->db->trans_start();
			$insert_result = $this->db->insert('votes', $data);
			$insert_id = $this->db->insert_id();
   		 	$this->db->trans_complete();
			
	        if ($insert_result) {
	            // Update the # of votes
				if ($increment_vote_count) {
		            $new_num_votes = intval($datapoint_row->number_votes)+1; // we will reuse this value later...
		            $vote_data = array('number_votes' => $new_num_votes);
		            $this->db->where('id', $datapoint_row->id);
		            $this->db->update('datastreams', $vote_data); 
				}
	            
	            // Return JSON Value
				$response_output = array();
				$response_output["vote_id"] = $insert_id;
				$response_output["number_votes"] = $new_num_votes;
				
	            $this->output
	                ->set_content_type('application/json')
	                ->set_output(json_encode($response_output));
	        } else {
	            $this->output
	                ->set_content_type('application/json')
	                ->set_output(json_encode("Error: Could not insert data into the database."));
	        }
		} else {
			// Update DB
			$vote_data = array('answer' => $answer);
            $this->db->where('id', $vote_id);
            $this->db->update('votes', $vote_data);
			
			$response_output = array();
			$response_output["vote_id"] = $vote_id;
			$response_output["number_votes"] = intval($datapoint_row->number_votes);
			
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($response_output));
		}
        
        

    }

    public function new_reading()
    {
        $this->load->database();
        $device_id = $this->uri->segment(3); // device_id
        $sensor_id = $this->uri->segment(4); // sensor_id
        $timestamp = $this->uri->segment(5); // timestamp

        // Add new item to database
        $data = array(
           'sensor_id' => $sensor_id,
           'device_id' => $device_id,
           'datapoint' => $timestamp,
           'number_votes' => 0 
        );

        if ($this->db->insert('datastreams', $data)) {
            echo "Data inserted.";
        } else {
            echo "Error: data insertion failed.";
        }

    }
	
    public function datapointsForSensor()
    {
        $this->load->database();
        $sensor_id = $this->input->get('sensor_id');
        $device_id = $this->input->get('device_id');

        // Grab Form Input
        $session_id = $this->input->get('session_id');

        if(!$device_id || !$sensor_id) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode("Error: session_id and device_id argument is required."));
        }

        // Execute Query
        $this->db->where(array('device_id' => $device_id, 'sensor_id'=>$sensor_id)); 
        $query = $this->db->get('datastreams');

        // Set Header to JSON
        $results = array();
        foreach ($query->result() as $row)
        {
            array_push($results,$row);
        }

        // Return JSON Value
		$origin = $this->input->get_request_header('Origin', TRUE);
		if (in_array($origin, $this->allowed_origins)) {
			$this->output->set_header("Access-Control-Allow-Origin: {$origin}");
		}
        $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($results));
        

    }

    public function retreive_data_from_dropbox($filepath)
    {
        $params['key'] = 'key';
        $params['secret'] = 'secret';
        $oath_token = 'oath_token';
        $oauth_token_secret = 'secret';
        $params['access'] = array('oauth_token'=>$oath_token, 'oauth_token_secret' => $oauth_token_secret);
        $this->load->library('dropbox', $params);

        $content = $this->dropbox->get(false,$filepath);
        if ($content=='{"error": "File not found"}') {
            return false;
        } 
        return $content;

    }

    public function add_data_to_dropbox($dbpath,$filepath)
    {
        $params['key'] = 'key';
        $params['secret'] = 'secret';
        $oath_token = 'oath_token';
        $oauth_token_secret = 'secret';
        $params['access'] = array('oauth_token'=>$oath_token, 'oauth_token_secret' => $oauth_token_secret);
        $this->load->library('dropbox', $params);

        $this->dropbox->add($dbpath, $filepath, array(),$root='dropbox');
        return true;

    }

    public function image()
    {

        $rootpath = '/Zensors';
        $device_id = $this->uri->segment(3); // device_id
        $sensor_id = $this->uri->segment(4); // sensor_id
        $timestamp = $this->uri->segment(5); // timestamp
        $content = $this->retreive_data_from_dropbox($rootpath . "/" . $device_id . "/" . $sensor_id . "/" . $timestamp . ".png");

        if (!$content) {
            show_404('Cannot find "'.$rootpath . "/" . $device_id . "/" . $sensor_id . "/" . $timestamp . ".png"."'");
        } else {
            $this->output->set_content_type('image/png');
            $this->output->set_output($content);
        }
    }

}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */