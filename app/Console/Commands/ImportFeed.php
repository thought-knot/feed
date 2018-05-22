<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Product;
use Validator;

class ImportFeed extends Command
{
    // Set base line number
    private $line_num = 1; // 0 == No Headers, 1 == Headers

    // Set up Error reporting array
    private $errors = [];

    // Count of Products Imported
    private $products_imported = 0;

    // Count of Products to Import
    private $products_to_import = 0;

    // Create the Validator
    private $validation_rules = [
        'strProductCode'  => 'required|unique:tblproductdata',
        'strProductName'  => 'required',
        'strProductDesc'  => 'required',
        'intStock'        => 'required|integer|stock_level:decCost,5,10',
        'decCost'         => 'required|numeric|max:1000',
        'blnDiscontinued' => 'in:,yes,no',
    ];

    private $validation_messages = [
        'strProductCode.required' => "Product Code is required",
        'strProductCode.unique' => "Product Code must be unique in Products Table",
        'strProductCode.required' => "Product Name is required",
        'strProductDesc.required' => "Product Description is required",
        'intStock.required' => 'Stock Level is required',
        'intStock.integer' => 'Stock Level must be an integer',
        'intStock.stock_level' => 'Stock Level must be greater than 10 to import with a cost lower than £5',
        'decCost.required' => "Cost is required",
        'decCost.numeric' => "Cost must be numeric",
        'decCost.max' => "Cost must be less than £1000",
        'blnDiscontinued.in' => "Discontinued can only be 'yes','no' or empty"
    ];

    protected $signature = 'import:feed {feed_csv}';

    protected $description = 'Import a feed in the agreed format';

    public function __construct()
    {
        parent::__construct();

        // Set up complex validation for stock level / cost comparison. Format: stock_level:field,min_price,min_stock
        Validator::extend('stock_level', function ($attribute, $value, $parameters, $validator) {
            $data = $validator->getData();
            if (isset($data[$parameters[0]])) {
                $field = $data[$parameters[0]];
                if ($field > $parameters[1] || $value > $parameters[2]) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        });


    }

    public function handle()
    {

        // Get the filename from the command line
        $feed_csv = $this->argument('feed_csv');

        // Check the file has been uploaded to the correct place
        if (file_exists(storage_path().'/app/public/import/'.$feed_csv)) {
            $import_lines = [];
            $file = fopen(storage_path().'/app/public/import/'.$feed_csv, "r");

            // Capture the first line as header columns
            $file_headers = fgetcsv($file, 0, ",");

            while ($data = fgetcsv($file, 0, ",")) {
                $import_lines[] = $data;
                $this->products_to_import++;
            }
        } else {
            // If we can't find the file display an error.
            $this->info('Import file "'.storage_path().'/app/public/import/'.$feed_csv.'" not found');
        }

        // Two ways to do the next part. I have chosen the second of these two
        // 1. Assume the headers in the file are correct and the columns can be in any orders
        // $header_translation = [
        //     "Product Code"          => "strProductCode",
        //     "Product Name"          => "strProductName",
        //     "Product Description"   => "strProductDesc",
        //     "Stock"                 => "intStock",
        //     "Cost in GBP"           => "decCost",
        //     "Discontinued" => "blnDiscontinued",
        // ];
        // $correct_headers = [];
        // foreach ($file_headers as $column_name) {
        //     $correct_headers[] = $header_translation[$column_name]; 
        // }

        // 2. Assume the columns are in the right order but the column names may be incorrect.
        $correct_headers = [
            "strProductCode",
            "strProductName",
            "strProductDesc",
            "intStock",
            "decCost",
            "blnDiscontinued",
        ];

        // Work through each line in the file and cross reference the validator

        if (isset($import_lines)) {

            foreach ($import_lines as $line) {
                $this->line_num++;

                if ($import_product = $this->validateLine($line,$correct_headers)) {
                    $new_product = new Product($import_product);
                    $new_product->blnDiscontinued = $new_product->blnDiscontinued == 'yes' ? 1 : 0;
                    $new_product->dtmDiscontinued = $new_product->blnDiscontinued == 1 ? date('Y-m-d H:i:s') : null;
                    $new_product->save();

                    $this->products_imported++;
                }
            }

            $this->info($this->products_imported."/".$this->products_to_import." products imported");

            if (count($this->errors) > 0) {
                $this->info('Errors:');
                foreach ($this->errors as $error) {
                    $this->info($error);
                }
            }


        } else {
            $this->info('No lines to import');
        }


    }

    private function validateLine($line,$headers) {

        // Check to see if there are the correct number of columns
        if (count($line) != count($headers)) {
            $this->errors[] = "Incorrect number of columns (Line ".$this->line_num.")";
            return false;
        }

        $import_product = array_combine($headers,$line);

        $validator = Validator::make($import_product,$this->validation_rules,$this->validation_messages);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->errors[] = $error." (Line ".$this->line_num.")";
            }

            return false;
        }

        return $import_product;
    }
}
