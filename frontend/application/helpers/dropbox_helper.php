<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
Copyright (C) 2011 by Jim Saunders

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

/**
 * Defines the different OAuth Signing algorithms. You 
 * should use this instead of writing them out each time.
 */
class DROPBOX_TOKENS
{
    const KEY = 'key';
    const SECRET = 'secret';
    const OAUTH_TOKEN = 'oath_token';
    const OAUTH_TOKEN_SECRET = 'oath_token_secret';
}

public function retreive_data_from_dropbox($filepath)
{
    $params['key'] = DROPBOX_TOKENS::KEY;
    $params['secret'] = DROPBOX_TOKENS::SECRET;
    $oath_token = DROPBOX_TOKENS::OAUTH_TOKEN;
    $oauth_token_secret = DROPBOX_TOKENS::OAUTH_TOKEN_SECRET;
    $params['access'] = array('oauth_token'=>$oath_token, 'oauth_token_secret' => $oauth_token_secret);
    $this->load->library('dropbox', $params);

    $content = $this->dropbox->get(false,$filepath);
    return $content;

}

/* ./application/helpers/oauth_helper.php */
?>