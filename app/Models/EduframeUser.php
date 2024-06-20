<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EduframeUser extends Model
{

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'eduframe_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'eduframe_id',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'slug',
        'avatar_url',
        'roles',
        'notes_user',
        'description',
        'employee_number',
        'student_number',
        'teacher_headline',
        'teacher_description',
        'teacher_enrollments_count',
        'locale',
        'wants_newsletter',
        'address',
        'custom',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'roles'      => 'array',
        'address'    => 'object',
        'custom'     => 'object',
    ];

    /**
     * Get the enrollments for the user.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'user_eduframe_id', 'eduframe_id');
    }

    /**
     * Get the user's RIZIV number
     * 
     * @return string
     */
    public function getRizivNumberAttribute()
    {
        return property_exists($this->custom, 'riziv-nummer') ? $this->custom->{'riziv-nummer'} : null;
    }

    /**
     * Import the data from Eduframe response
     * 
     * @param array
     */
    public function importEduframeRecord($record)
    {
        $map = [
            'eduframe_id'               => 'id',
            'first_name'                => 'first_name',
            'middle_name'               => 'middle_name',
            'last_name'                 => 'last_name',
            'email'                     => 'email',
            'slug'                      => 'slug',
            'avatar_url'                => 'avatar_url',
            'roles'                     => 'roles',
            'notes_user'                => 'notes_user',
            'description'               => 'description',
            'employee_number'           => 'employee_number',
            'student_number'            => 'student_number',
            'teacher_headline'          => 'teacher_headline',
            'teacher_description'       => 'teacher_description',
            'teacher_enrollments_count' => 'teacher_enrollments_count',
            'locale'                    => 'locale',
            'wants_newsletter'          => 'wants_newsletter',
            'address'                   => 'address',
            'custom'                    => 'custom',
        ];

        EduframeUser::updateOrCreate(
            ['eduframe_id' => $record['id']],
            array_map(fn($key) => $record[$key], $map)
        );
    }
}
