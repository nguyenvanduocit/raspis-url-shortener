<?php 

class Bookmark extends Eloquent
{
	protected $table = 'bookmarks';
    protected $shuffleData = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    protected $shortenRules = array(
        'long_url' => 'required|url'
    );

    protected $shortenMessages = array(
        'url' => 'Invalid URL provided in :attribute'
    );

    public function user()
    {
        $this->belongsTo('User');
    }

    public function getShortValRules()
    {
        return $this->shortenRules;
    }

    public function getShortValMessages()
    {
        return $this->shortenMessages;
    }

    public function codeExists( $shortCode )
    {
        return $this->where('shortened_code', '=', $shortCode)->count() !== 0;
    }

    public function deleteBookmark( $id, $userId )
    {
        $toDelete = $this->find( $id );

        if ( $toDelete && $toDelete->user_id == $userId ) {
            
            if ( $toDelete->delete() ) {
                return true;
            }
        }
     
        return false;
    }

    public function generateShortCode()
    {
        $shortCode = '';

        do {
            
            $shuffled = str_shuffle( $this->shuffleData );
            $shortCode = substr($shuffled, 0, 6);

        } while ( $this->codeExists( $shortCode ) );

        return $shortCode;
    }

    public function findPotentialShortcode( $url )
    {
        $bookmark = $this->where('url', '=', $url)
                          ->whereNull('user_id')
                          ->select('shortened_code')
                          ->first();

        if ( $bookmark ) {
            return $bookmark->shortened_code;
        }

        return false;
    }

    public function shorten( $longUrl, $userId = false )
    {
        // If the user is not logged in
        // ..we don't need a new entry if the URL provided was
        // ..already shortened by someone else
        if( !$userId ) {
            
            $shortCode = $this->findPotentialShortcode( $longUrl );
            
            if ( $shortCode ) {
                return $shortCode;
            }
        }

        $shortCode = $this->generateShortCode();

        $this->url = $longUrl;
        $this->shortened_code = $shortCode;

        if ( $userId ) {
            $this->user_id = $userId;  
        } 

        $bookmark = $this->save();

        if ( $bookmark ) {
            return $shortCode;
        }

        return false;
    }
}