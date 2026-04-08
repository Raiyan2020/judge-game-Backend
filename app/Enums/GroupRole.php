<?php
namespace App\Enums;

enum GroupRole: string
{
    case JUDGE = 'judge';          
    case CONSULTANT = 'consultant'; 
    case LAWYER = 'lawyer';        
    case CITIZEN = 'citizen';    
}