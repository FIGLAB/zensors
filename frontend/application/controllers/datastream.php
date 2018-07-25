<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Datastream extends CI_Controller {

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
    
	public function index()
	{
		
        $this->load->database();

	}

    public function update_sensor()
    {
        $this->load->database();
        $device_id = $this->uri->segment(3); // device_id
        $sensor_id = $this->uri->segment(4); // sensor_id

        // Get POST data
        $sensor_data = $this->input->post('sensor_data'); // sensor data in JSON format
        echo $sensor_data;
        $split = explode("\n",$sensor_data);

        if (count($split)<5) {
            echo "Error: something was wrong with the provided sensor data: '". $sensor_data . "'";
            return;
        }

        $settings = json_decode($split[0]);
        $lassoPoints = $split[1];
        $flag = $split[2];
        $dataType = json_decode($split[3]);
        $lastRun = $split[4];

        $data = array(
           'data' => $sensor_data,
           'sensor_name' => $settings->name,
           'sensor_question' => $settings->capability,
           'sensor_frequency' => $settings->frequency,
           'sensor_datatype' => $settings->datatype->label,
           'sensor_datatype_values' => $split[3],
           'sensor_obfuscation' => $settings->obfuscation,
           'active' => ($settings->active || $settings->active=="true") ? "yes" : "no",
           'sensor_subwindowpoints' => $lassoPoints
        );

        // Check if Sensor ID exists for the given device ID
        $query = $this->db->get_where('sensors',array('device_id'=>$device_id, 'sensor_id'=>$sensor_id));
        
        if ($query->num_rows() > 0) {
            // Update
            $this->db->where('sensor_id', $sensor_id);
            $this->db->update('sensors', $data);
            echo "Data updated.";

        } else {
            // Insert
            $data['data'] = $sensor_data;
            $data['sensor_id'] = $sensor_id;
            $data['device_id'] = $device_id;
            
            if ($this->db->insert('sensors', $data)) {
                echo "Data inserted.";
            } else {
                echo "Error: data insertion failed.";
            }
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

        $explore_id = $this->input->get('explore_id',TRUE);
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