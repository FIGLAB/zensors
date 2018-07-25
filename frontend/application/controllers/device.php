<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Device extends CI_Controller {

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
    
	public function register()
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
        $this->load->view('register_device', $data);
	}
	
	public function new_device()
	{
		
        $this->load->database();
		$this->load->helper('url');

        // Grab URL Params
        $data = array();
		$data["device_id"]= $this->input->get_post('device_id');
        $data["area_id"]= $this->input->get_post('area_id');
        $data["model"]= $this->input->get_post('model');
        $data["image_width"]= $this->input->get_post('image_width');
        $data["image_height"]= $this->input->get_post('image_height');
        $data["display_density"]= $this->input->get_post('display_density');
        $data["os_version"]= $this->input->get_post('os_version');
        $data["nickname"]= $this->input->get_post('nickname');
        $data["online"]= true;
		$data["stealth_mode"]= false;

        // Fetch datapoints
        $success = false;
    	if ($this->db->insert('devices', $data)) {
            $success = true;
        } 

        if ($success) {
        	redirect('dashboard?access=cmuhcii');
        } else {
        	$this->output
                ->set_content_type('application/json')
                ->set_output("Error: something went wrong during the registration process.");
        }

	}
	
    public function stealth()
    {
        
        $this->load->database();
        $this->load->helper('url');

        // Grab URL Params
        $device_id = $this->uri->segment(3);
        $data["stealth_mode"] = $this->input->get('stealth_mode');

        $success = false;
        $this->db->where('device_id', $device_id);
        if ($this->db->update('devices', $data)) {
            $success = true;
        }

        if ($success) {
            redirect('/', 'location');
        } else {
            $this->output
                ->set_content_type('application/json')
                ->set_output("Error: something went wrong during the registration process.");
        }

    }

}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */