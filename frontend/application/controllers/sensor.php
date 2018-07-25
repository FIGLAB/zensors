<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Sensor extends CI_Controller {

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
    
	public function settings()
	{
		
        $this->load->database();

        // Grab URL Params
        $device_id = $this->uri->segment(3); // device_id
        $sensor_id = $this->uri->segment(4); // sensor_id

        // Check if Form was submitted
        $data["form_submitted"] = "false";
		$data["sensor"] = new stdClass();
        $form_submitted = $this->input->post('settings_updated');
        if ($form_submitted=="submittedFromForm") {
            $data["form_submitted"] = "true";
            $data["sensor"]->sensor_name = $this->input->post('sensor_name');
            $data["sensor"]->sensor_question = $this->input->post('sensor_question');
            $data["sensor"]->sensor_frequency = $this->input->post('sensor_frequency');
            $data["sensor"]->sensor_datatype = $this->input->post('sensor_datatype');
            $data["sensor"]->sensor_datatype_values = $this->input->post('sensor_datatype_values');
            $data["sensor"]->sensor_obfuscation = $this->input->post('sensor_obfuscation');
            $data["sensor"]->active = $this->input->post('sensor_active');
            $data["sensor"]->sensor_subwindowpoints = $this->input->post('sensor_subwindowpoints');

            // Update Database
            $updated_settings = array();
            $updated_settings = array(
                "sensor_name" => $data["sensor"]->sensor_name,
                "sensor_question" => $data["sensor"]->sensor_question,
                "sensor_frequency" => $data["sensor"]->sensor_frequency,
                "sensor_datatype" => $data["sensor"]->sensor_datatype,
                "sensor_datatype_values" => $data["sensor"]->sensor_datatype_values,
                "sensor_obfuscation" => $data["sensor"]->sensor_obfuscation,
                "active" => $data["sensor"]->active,
                "sensor_subwindowpoints" => $data["sensor"]->sensor_subwindowpoints,
                "last_updated" => time() * 1000 // milliseconds
            );

            // Update the DB
            // Case A: Is this a new sensor?
            $data["update_success"] = "false";
            $check_query = $this->db->get_where('sensors',array('sensor_id' => $sensor_id, 'device_id' => $device_id));
            if ($check_query->num_rows()==0) {
                $new_sensor = $updated_settings;
                $new_sensor["sensor_id"] = $sensor_id;
                $new_sensor["device_id"] = $device_id;
                if ($this->db->insert('sensors', $new_sensor)) {
                    $data["update_success"] = "true";
                } 
            } // Case B: Update currently existing sensor
              else {
                $this->db->where('sensor_id', $sensor_id);
                if ($this->db->update('sensors', $updated_settings)) {
                    $data["update_success"] = "true";
                } 
            }
            
        }

        // Generate Data for HTML
        $sensor_exists = false;
        $devices = array();
        $device_query = $this->db->get_where('devices');
        foreach ($device_query->result() as $row) {
            $devices[$row->device_id] = array();
            $devices[$row->device_id]["properties"] = $row;

            $sensor_query = $this->db->get_where('sensors', array('device_id' => $row->device_id));

            $devices[$row->device_id]["sensors"] = array();
            foreach ($sensor_query->result() as $sensor_row) {
                if ($sensor_row->sensor_id==$sensor_id) {
                    $sensor_exists = true;
                    $data["sensor"] = $sensor_row;
                }
                array_push($devices[$row->device_id]["sensors"],$sensor_row);
            }
        }

        if (!$sensor_exists) {
            show_404('Error: Cannot find "serialize/'.$device_id . "/" . $sensor_id);
        }

        $data["devices"] = $devices;
        $data["sensor_id"] = $sensor_id;
        $data["device_id"] = $device_id;


        // Fetch datapoints
        $data['datapoints'] = array();
        $query = $this->db->get_where('datastreams',array('sensor_id' => $sensor_id));
        foreach ($query->result() as $row)
        {
            array_push($data['datapoints'],$row);
        }

        // Load our view and its corresponding template variables
        $this->load->view('sensor_settings', $data);

	}

    public function visualize()
    {
        
        $this->load->database();

        // Grab URL Params
        $device_id = $this->uri->segment(3); // device_id
        $sensor_id = $this->uri->segment(4); // sensor_id

        $data["sensor_id"] = $sensor_id;
        $data["device_id"] = $device_id;

        $graph_width = $this->input->get('width');
        $graph_height = $this->input->get('height');

        $data["graph_width"] = ($graph_width) ? $graph_width : 600;
        $data["graph_height"] = ($graph_height) ? $graph_height : 500;
		
		////////////////////////////////////////////
		// Grab Sensor Details
		////////////////////////////////////////////
        $where = array();
        $where['sensor_id'] = $sensor_id;
        $where['device_id'] = $device_id;
        
        // Execute Query
        $this->db->where($where); 
        $query = $this->db->get('sensors');

        // Set Header to JSON
        $sensor_details = array();
		if ($query->num_rows() > 0)
		{
		   $sensor_details = $query->row();
		}
		$data["sensor_details"] = json_encode($sensor_details);
		
		////////////////////////////////////////////
		// Grab Label Points From DB
		////////////////////////////////////////////
        $session_id = $this->input->get('session_id');
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
		
		$data["datapoints"] = json_encode($results);
		
        // Load our view and its corresponding template variables
		if ($sensor_details->sensor_datatype=="YESNO") {
			$this->load->view('sensor_viz_yesno', $data);
		} else if ($sensor_details->sensor_datatype=="NUMBER") {
			$this->load->view('sensor_viz_number', $data);
		} else if ($sensor_details->sensor_datatype=="SCALE") {
			$this->load->view('sensor_viz_scale', $data);
		} else if ($sensor_details->sensor_datatype=="MULTIPLECHOICE") {
			$this->load->view('sensor_viz_multiplechoice', $data);
		} else {
			$this->load->view('sensor_viz', $data);
		}
    }
	
    public function realtime()
    {
        $this->load->database();

        // Grab URL Params
        $device_id = $this->uri->segment(3); // device_id
        $sensor_id = $this->uri->segment(4); // sensor_id

        $data["sensor_id"] = $sensor_id;
        $data["device_id"] = $device_id;

        $graph_width = $this->input->get('width');
        $graph_height = $this->input->get('height');

        $data["graph_width"] = ($graph_width) ? $graph_width : 600;
        $data["graph_height"] = ($graph_height) ? $graph_height : 500;
		
		////////////////////////////////////////////
		// Grab Sensor Details
		////////////////////////////////////////////
        $where = array();
        $where['sensor_id'] = $sensor_id;
        $where['device_id'] = $device_id;
        
        // Execute Query
        $this->db->where($where); 
        $query = $this->db->get('sensors');

        // Set Header to JSON
        $sensor_details = array();
		if ($query->num_rows() > 0)
		{
		   $sensor_details = $query->row();
		}
		$data["sensor_details"] = json_encode($sensor_details);
		
		////////////////////////////////////////////
		// Grab Label Points From DB
		////////////////////////////////////////////
        $session_id = $this->input->get('session_id');
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
		
		$data["datapoints"] = json_encode($results);
		
        // Load our view and its corresponding template variables
		$this->load->view('sensor_viz_realtime', $data);
    }
	
    public function realtime_mobile()
    {
        $this->load->database();

        // Grab URL Params
        $device_id = $this->uri->segment(3); // device_id
        $sensor_id = $this->uri->segment(4); // sensor_id

        $data["sensor_id"] = $sensor_id;
        $data["device_id"] = $device_id;

        $graph_width = $this->input->get('width');
        $graph_height = $this->input->get('height');

        $data["graph_width"] = ($graph_width) ? $graph_width : 600;
        $data["graph_height"] = ($graph_height) ? $graph_height : 500;
		
		////////////////////////////////////////////
		// Grab Sensor Details
		////////////////////////////////////////////
        $where = array();
        $where['sensor_id'] = $sensor_id;
        $where['device_id'] = $device_id;
        
        // Execute Query
        $this->db->where($where); 
        $query = $this->db->get('sensors');

        // Set Header to JSON
        $sensor_details = array();
		if ($query->num_rows() > 0)
		{
		   $sensor_details = $query->row();
		}
		$data["sensor_details"] = json_encode($sensor_details);
		
		////////////////////////////////////////////
		// Grab Label Points From DB
		////////////////////////////////////////////
        $session_id = $this->input->get('session_id');
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
		
		$data["datapoints"] = json_encode($results);
		
        // Load our view and its corresponding template variables
		$this->load->view('sensor_viz_realtime_mobile', $data);
    }

    public function delete()
    {
        $this->load->database();
        $this->load->helper('url');
        $device_id = $this->uri->segment(3); // device_id
        $sensor_id = $this->uri->segment(4); // sensor_id

        $success = 0;
        if ($this->db->delete('sensors', array('sensor_id' => $sensor_id, 'device_id' => $device_id))) 
            $success += 1;
        if ($this->db->delete('datastreams', array('sensor_id' => $sensor_id, 'device_id' => $device_id))) 
            $success += 1;
        if ($this->db->delete('votes', array('sensor_id' => $sensor_id, 'device_id' => $device_id))) 
            $success += 1;

        if ($success==0) {
            $this->output
                ->set_content_type('application/json')
                ->set_output("Error: Unable to delete sensor_id='{$sensor_id}'");
        } else {
            redirect('/dashboard/device/'.$device_id, 'location');
        }

    }

    public function create()
    {
        $this->load->database();
        $device_id = $this->uri->segment(3); // device_id
        $sensor_id = $this->generateRandomString(); // create random sensorID

        // Generate Data for Navigation
        $devices = array();
        $device_query = $this->db->get_where('devices');
        foreach ($device_query->result() as $row) {
            $devices[$row->device_id] = array();
            $devices[$row->device_id]["properties"] = $row;
            
            $sensor_query = $this->db->get_where('sensors', array('device_id' => $row->device_id));

            $devices[$row->device_id]["sensors"] = array();
            foreach ($sensor_query->result() as $sensor_row) {
                array_push($devices[$row->device_id]["sensors"],$sensor_row);
            }
        }
		
        $data["devices"] = $devices;
        $data["device_id"] = $device_id;
        $data["sensor_id"] = $sensor_id;
        $data["current_device"] = $devices[$device_id]["properties"];
		
		$endpoint_query = $this->db->get_where('backends', array('endpoint' => 'image_handler'));
		$handler = $sensor_query->result()[0];
		$data['latest_img'] = "http://{$handler->ip}:{$handler->port}/device/{$device_id}/latest_image";

        // Load our view and its corresponding template variables
        $this->load->view('new_sensor', $data);

    }

    // Serialize Sensor Settings into a MetaSensor JSON Parseable Format */
    public function serialize()
    {
        
        $this->load->database();

        // Grab URL Params
        $device_id = $this->uri->segment(3); // device_id
        $sensor_id = $this->uri->segment(4); // sensor_id

        // Generate Data for Navigation
        $query = $this->db->get_where('sensors', array('sensor_id' => $sensor_id, 'device_id' => $device_id));
        $num_rows = $query->num_rows();
        if ($num_rows==1) {
            $sensor = $query->row();

            $settings = array();
            $settings["id"] = $sensor->sensor_id;
            $settings["name"] = $sensor->sensor_name;
            $settings["capability"] = $sensor->sensor_question;
            $settings["frequency"] = $sensor->sensor_frequency;
            $settings["obfuscation"] = $sensor->sensor_obfuscation;
            $settings["rawDataWidth"] = 0;
            $settings["rawDataHeight"] = 0;
            $settings["dataPoints"] = json_decode($sensor->sensor_subwindowpoints);
            $settings["active"] = $sensor->active;

            // DataType
            if ($sensor->sensor_datatype=="YESNO") {
                $settings["datatype"] = array("label" => "YESNO");
            } else if ($sensor->sensor_datatype=="NUMBER") {
                $settings["datatype"] = array("label" => "NUMBER");
            } else if ($sensor->sensor_datatype=="SCALE") {
                $settings["datatype"] = array("label" => "SCALE", "scaleValuePairs" => $sensor->sensor_datatype_values);
            } else if ($sensor->sensor_datatype=="MULTIPLECHOICE") {
                $settings["datatype"] = array("label" => "MULTIPLECHOICE", "choices" => $sensor->sensor_datatype_values);
            } else if ($sensor->sensor_datatype=="FREETEXT") {
                $settings["datatype"] = array("label" => "FREETEXT", "hint" => "");
            }

            $output = json_encode($settings);
            $output .= "\n" . $sensor->sensor_subwindowpoints;

            if ($sensor->sensor_datatype=="SCALE") {
                $output .= "\n" . 0;
                $output .= "\n" . $sensor->sensor_datatype_values;
            } else if ($sensor->sensor_datatype=="MULTIPLECHOICE") {
                $output .= "\n" . 2;
                $output .= "\n" . $sensor->sensor_datatype_values;
            } else {
                $output .= "\n" . 1;
                $output .= "\n" . $sensor->sensor_datatype_values;
            }

            $output .= "\n" . 0;    // force the Sensor to Run immediately once new settings are received...

            $this->output
                ->set_content_type('application/json')
                ->set_output($output);

        } else {
            show_404('Cannot find "serialize/'.$device_id . "/" . $sensor_id);
        }


    }
	
    public function demo_serialize()
    {
        
        $this->load->database();

        // Grab URL Params
        $device_id = $this->uri->segment(3); // device_id
        $sensor_id = $this->uri->segment(4); // sensor_id

        // Generate Data for Navigation
        $query = $this->db->get_where('demo_sensors', array('sensor_id' => $sensor_id, 'device_id' => $device_id));
        $num_rows = $query->num_rows();
        if ($num_rows==1) {
            $sensor = $query->row();

            $settings = array();
            $settings["id"] = $sensor->sensor_id;
            $settings["name"] = $sensor->sensor_name;
            $settings["capability"] = $sensor->sensor_question;
            $settings["frequency"] = $sensor->sensor_frequency;
            $settings["obfuscation"] = $sensor->sensor_obfuscation;
            $settings["rawDataWidth"] = 0;
            $settings["rawDataHeight"] = 0;
            $settings["dataPoints"] = json_decode($sensor->sensor_subwindowpoints);
            $settings["active"] = $sensor->active;

            // DataType
            if ($sensor->sensor_datatype=="YESNO") {
                $settings["datatype"] = array("label" => "YESNO");
            } else if ($sensor->sensor_datatype=="NUMBER") {
                $settings["datatype"] = array("label" => "NUMBER");
            } else if ($sensor->sensor_datatype=="SCALE") {
                $settings["datatype"] = array("label" => "SCALE", "scaleValuePairs" => $sensor->sensor_datatype_values);
            } else if ($sensor->sensor_datatype=="MULTIPLECHOICE") {
                $settings["datatype"] = array("label" => "MULTIPLECHOICE", "choices" => $sensor->sensor_datatype_values);
            } else if ($sensor->sensor_datatype=="FREETEXT") {
                $settings["datatype"] = array("label" => "FREETEXT", "hint" => "");
            }

            $output = json_encode($settings);
            $output .= "\n" . $sensor->sensor_subwindowpoints;

            if ($sensor->sensor_datatype=="SCALE") {
                $output .= "\n" . 0;
                $output .= "\n" . $sensor->sensor_datatype_values;
            } else if ($sensor->sensor_datatype=="MULTIPLECHOICE") {
                $output .= "\n" . 2;
                $output .= "\n" . $sensor->sensor_datatype_values;
            } else {
                $output .= "\n" . 1;
                $output .= "\n" . $sensor->sensor_datatype_values;
            }

            $output .= "\n" . 0;    // force the Sensor to Run immediately once new settings are received...

            $this->output
                ->set_content_type('application/json')
                ->set_output($output);

        } else {
            show_404('Cannot find "serialize/'.$device_id . "/" . $sensor_id);
        }


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

    public function viewport()
    {
        $rootpath = '/Zensors';
        $device_id = $this->uri->segment(3); // device_id
        $content = $this->retreive_data_from_dropbox($rootpath . "/" . $device_id . "/viewport.png");

        if (!$content) {
            show_404('Cannot find "'.$rootpath . "/" . $device_id . "/viewport.png");
        } else {
            $this->output->set_content_type('image/png');
            $this->output->set_output($content);
        }
    }

    public function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }


}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */