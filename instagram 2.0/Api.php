<?php
class Api
{
    /** API URL */
    public $api_url;
    
    /** Your API key */
    public $api_key;

    public function __construct($api_key, $api_url) {
        $this->api_key = $api_key;
        $this->api_url = $api_url;
    }

    /** Add order */
    public function order($data)
    {
        $post = array_merge(['key' => $this->api_key, 'action' => 'add'], $data);
        return json_decode($this->connect($post));
    }

    /** Get order status */
    public function status($order_id)
    {
        return json_decode(
            $this->connect([
                'key' => $this->api_key,
                'action' => 'status',
                'order' => $order_id
            ])
        );
    }

    /** Connect to API */
    private function connect($post)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
?>
