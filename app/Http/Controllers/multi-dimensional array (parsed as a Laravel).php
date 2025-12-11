multi-dimensional array (parsed as a Laravel Collection).



use Illuminate\Support\Collection;

class OrderController extends Controller
{
    public function processApiOrders()
    {
        // 1. Simulate receiving the API data as a multi-dimensional PHP array
        $apiData = [
            /* ... the nested array data from above ... */
        ];

        // Convert the PHP array into a Laravel Collection for easy manipulation
        $orders = new Collection($apiData);

        // 2. Map and Calculate Totals
        // The map() function iterates over the main array (the orders)
        $processedOrders = $orders->map(function ($order) {

            // Get the 'items' array for the current order and wrap it in a Collection
            $itemsCollection = collect($order['items']);

            // Iterate over the inner array (the items) to calculate the line total
            $itemsWithLineTotals = $itemsCollection->map(function ($item) {
                // Calculation on the innermost data
                $item['line_total'] = $item['price'] * $item['qty'];
                return $item;
            });

            // Calculate the grand total for the whole order by summing the 'line_total' column
            $order['total_amount'] = $itemsWithLineTotals->sum('line_total');

            // Replace the original 'items' with the updated collection
            $order['items'] = $itemsWithLineTotals;

            return $order;
        });

        // 3. Return the processed multi-dimensional array as a JSON response
        return response()->json($processedOrders);
    }
}