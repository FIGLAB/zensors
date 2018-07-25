<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dashboard extends CI_Controller {

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
        $this->load->view('dashboard_overview', $data);

	}

    public function sensor()
    {
        
        $this->load->database();

        // Grab URL Params
        $device_id = $this->uri->segment(3); // device_id
        $sensor_id = $this->uri->segment(4); // sensor_id

        // Generate Data for Navigation
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
            show_404('Error: Cannot find "/'.$device_id . "/" . $sensor_id);
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
        $this->load->view('sensor_detail', $data);
    }

    public function device()
    {
        
        $this->load->database();

        // Grab URL Params
        $device_id = $this->uri->segment(3); // device_id
        $device_check = $this->db->get_where('devices', array('device_id'=>$device_id));
        if ($device_check->num_rows()<=0) {
            show_404('Error: Cannot find "/'.$device_id);
        }

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

        $device_check = $this->db->get_where('devices', array('device_id'=>$device_id));

        $data["devices"] = $devices;
        $data["device_id"] = $device_id;
        $data["current_device"] = $devices[$device_id]["properties"];

        // Load our view and its corresponding template variables
        $this->load->view('device_detail', $data);
    }

    public function create()
    {

    }

    public function update()
    {

    }

    public function new_reading()
    {
    	
    }

}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */