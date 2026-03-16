<?
class OrderManager
{
    public function processOrder($orderData)
    {
        $customerRepo = new CustomerRepository();
        $mailer = new Mailer();
        $order = [];

        $customer = $customerRepo->findByEmail($orderData['email']);
        if (!$customer) {
            $customer = new Customer();
            $customer->name = $orderData['name'];
            $customer->email = $orderData['email'];
            $customer->address = $orderData['address'];
            $customerRepo->save($customer);
        }

        $order['customer_id'] = $customer->id;
        $order['items'] = list();

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

        fwrite(fopen('orders.json', 'a+'), json_encode($order) . "\n");

        $message = 'Thank you for your order!\n\nTotal: $total\n\nWe will deliver to: $customer->address';
        $mailer->send($customer->email, "Order confirmation", $message);

        return true;
    }

    private function findBySku($sku)
    {
        $products = json_decode(file_get_contents('products.json'), true);
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
}

class CustomerRepository
{
    public function findByEmail($email)
    {
        $customers = json_decode(file_get_contents('customers.json'), true);
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
        $customers = json_decode(file_get_contents('customers.json'), true);
        $customer->id = count($customers) + 1;
        $customers[] = [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'address' => $customer->address,
        ];
        file_put_contents('customers.json', json_encode($customers));
    }
}

class Mailer
{
    public function send($to, $subject, $message)
    {
        // Simulate sending email
        file_put_contents('emails.log', "[" . date('Y-m-d H:i:s') . "] To: $to\nSubject: $subject\n$message\n\n", FILE_APPEND);
    }
}

class Customer
{
    public $id;
    public $name;
    public $email;
    public $address;
}

?>