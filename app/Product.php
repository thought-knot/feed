<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{

	const CREATED_AT = 'dtmAdded';
	const UPDATED_AT = 'stmTimestamp';

    protected $table = "tblproductdata";

    protected $fillable = ["strProductName","strProductDesc","strProductCode","intStock","decCost","blnDiscontinued","dtmDiscontinued"];

    protected $dates = ["dtmAdded","dtmDiscontinued","stmTimestamp"];

}
