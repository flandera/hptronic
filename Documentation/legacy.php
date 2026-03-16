<?php

class OrderManager
{
    private $customerRepository;
    private $mailer;

    /**
     * Dependencies are injected (or defaulted) to make the class easier to test
     */
    public function __construct($customerRepository = null, $mailer = null)
    {
        $this->customerRepository = $customerRepository ?: new CustomerRepository();
        $this->mailer = $mailer ?: new Mailer();
    }

    /**
     * Basic validation added
     */
    public function processOrder($orderData)
    {
        if (empty($orderData['email']) || empty($orderData['name']) || empty($orderData['address']) || empty($orderData['items']) || !is_array($orderData['items'])) {
            return false;
        }

        $order = array();

        $customer = $this->customerRepository->findByEmail($orderData['email']);
        if (!$customer) {
            $customer = new Customer();
            $customer->name = $orderData['name'];
            $customer->email = $orderData['email'];
            $customer->address = $orderData['address'];
            $this->customerRepository->save($customer);
        }

        $order['customer_id'] = $customer->id;
        $order['items'] = array();

        $total = 0;
        foreach ($orderData['items'] as $item) {
            $product = $this->findBySku($item['sku']);
            if (!$product) {
                // Ignore missing products silently
                continue;
            }

            $line = [];
            $line['sku'] = $product->sku;
            $line['price'] = $product->price;
            $line['quantity'] = $item['quantity'];
            $line['total'] = $product->price * $item['quantity'];
            $order['items'][] = $line;

            $total += $line['total'];
        }

        $order['total'] = $total;
        $order['created_at'] = date('Y-m-d H:i:s');

        $this->appendOrderToFile($order);

        $message = "Thank you for your order!" . PHP_EOL . PHP_EOL .
            "Total: " . $total . PHP_EOL . PHP_EOL .
            "We will deliver to: " . $customer->address;

        $this->mailer->send($customer->email, "Order confirmation", $message);

        return true;
    }

    /**
     * defensiver file handling for appending an order.
     */
    private function appendOrderToFile($order)
    {
        $handle = @fopen('orders.json', 'a+');
        if ($handle === false) {
            return;
        }

        fwrite($handle, json_encode($order) . "\n");
        fclose($handle);
    }


    private function findBySku($sku)
    {
        $products = $this->loadJsonFileAsArray('products.json');
        foreach ($products as $p) {
            if ($p['sku'] === $sku) {
                $product = new stdClass();
                $product->sku = $p['sku'];
                $product->price = $p['price'];
                return $product;
            }
        }
        return null;
    }

    /**
     * Shared, JSON loader to reduce repeated error-prone IO.
     */
    private function loadJsonFileAsArray($fileName)
    {
        if (!is_file($fileName)) {
            return array();
        }

        $contents = @file_get_contents($fileName);
        if ($contents === false || $contents === '') {
            return array();
        }

        $decoded = json_decode($contents, true);
        if (!is_array($decoded)) {
            return array();
        }

        return $decoded;
    }
}

/**
 * This repository should be in its own file
 * and be wired via dependency injection.
 */
class CustomerRepository
{
    public function findByEmail($email)
    {
        $customers = $this->loadJsonFileAsArray('customers.json');
        foreach ($customers as $c) {
            if ($c['email'] === $email) {
                $customer = new stdClass();
                $customer->id = $c['id'];
                $customer->name = $c['name'];
                $customer->email = $c['email'];
                $customer->address = $c['address'];
                return $customer;
            }
        }
        return null;
    }

    public function save($customer)
    {
        $customers = $this->loadJsonFileAsArray('customers.json');
        $customer->id = count($customers) + 1;
        $customers[] = [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'address' => $customer->address,
        ];
        file_put_contents('customers.json', json_encode($customers));
    }

    private function loadJsonFileAsArray($fileName)
    {
        if (!is_file($fileName)) {
            return array();
        }

        $contents = @file_get_contents($fileName);
        if ($contents === false || $contents === '') {
            return array();
        }

        $decoded = json_decode($contents, true);
        if (!is_array($decoded)) {
            return array();
        }

        return $decoded;
    }
}

/**
 * this logic should live in its own class.
 */
class Mailer
{
    public function send($to, $subject, $message)
    {
        // Simulate sending email
        file_put_contents('emails.log', "[" . date('Y-m-d H:i:s') . "] To: $to\nSubject: $subject\n$message\n\n", FILE_APPEND);
    }
}

/**
 * this logic should live in its own class.
 */
class Customer
{
    public $id;
    public $name;
    public $email;
    public $address;
}

?>