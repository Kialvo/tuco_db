<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoricalEntry extends Model
{
    /* the view you created in MySQL / MariaDB */
    protected $table = 'v_new_entries_filtered';

    /* primary key is still “id” */
    protected $primaryKey = 'id';

    /* no created_at / updated_at columns in the view */
    public $timestamps = false;

    /* ───── relationships identical to NewEntry ───── */
    public function contact ()  { return $this->belongsTo(Contact::class);  }
    public function language () { return $this->belongsTo(Language::class); }
    public function country ()  { return $this->belongsTo(Country::class);  }

    // app/Models/HistoricalEntry.php   (or wherever the model lives)

    public function categories()
    {
        return $this->belongsToMany(
            Category::class,       // related model
            'category_new_entry',  // pivot table
            'new_entry_id',        // FOREIGN KEY on the pivot pointing to *this* model
            'category_id'          // FOREIGN KEY on the pivot pointing to Category
        )->using(CategoryNewEntry::class)->withTimestamps();   // keep the custom pivot class if you need it
    }

}
