<?php

namespace App\Http\Controllers;

use App\DocumentFolder;
use App\DocumentUpload;
use App\User;
use Illuminate\Http\Request;
use Validator;
use App\Http\Controllers\Controller;
use App\PersonalDetail;
use App\PersonalDocumentUpload;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class ResourceController extends Controller
{
    public function createFolder(Request $request) {
        $validate = Validator::make($request->all(), [
            'folder_name' => 'required|unique:document_folders,folder_name,' . Auth::user()->id . ',user_id|max:255',
        ]);

        if ($validate->fails()) {
            return response([
                'message' => $validate->errors(),
            ], 422);
        }

        $data = $request->only('folder_name');
        $data['user_id'] = Auth::user()->id;
        $folder = DocumentFolder::create($data);
        return response()->json([
            "message" => "Folder Created.",
            "data" => $folder
        ]);

    }

    public function uploadDocuments(Request $request) {
        $validate = Validator::make($request->all(), [
            'document' => 'required',
            'folder_id' => 'required|min:0'
        ]);

        if ($validate->fails()) {
            return response([
                'message' => $validate->errors(),
            ], 422);
        }

        $document = $request->file('document');
        $data = [];
        $path = $this->uploadImage($document, $data);
        $data['document_folder_id'] = $request->folder_id;
        $document_upload = DocumentUpload::create($data);
        $document_upload->file_path = config('app.url').\Storage::url($document_upload->file_path);
        
        return response()->json([
            "message" => "Document Uploaded.",
            "data" => $document_upload
        ]);

    }

    public function getFolderDetails(Request $request) {
        $folders = DocumentFolder::with('documents')->get();
        foreach($folders as $folder) {
            foreach($folder->documents as $document) {
                $document->file_path = config('app.url').\Storage::url($document->file_path);
            }
        }
        return response()->json([
            "message" => "Folder Details.",
            "data" => $folders
        ]);
        return $folders;
    }

    public function updatePersonalDetails(Request $request) {
        $communication_types = $request->communication_types;
        $communication_devices = $request->communication_devices;
        $support_peoples = json_encode($request->support_peoples);
        $eating_drinking = $request->eating_drinking;
        $eating_drinking_description = json_encode($request->eating_drinking_description);
        $hygiene_toileting = $request->hygiene_toileting;
        $emotional_behavioural_need = json_encode($request->emotional_behavioural_need);
        $cultural_religious_need = json_encode($request->cultural_religious_need);
        
        $details = PersonalDetail::create([
            'user_id' => Auth::user()->id,
            'communication_types' => $communication_types,
            'communication_devices' => $communication_devices,
            'support_peoples' => $support_peoples,
            'eating_drinking' => $eating_drinking,
            'eating_drinking_description' => $eating_drinking_description,
            'hygiene_toileting' => $hygiene_toileting,
            'emotional_behavioural_need' => $emotional_behavioural_need,
            'cultural_religious_need' => $cultural_religious_need,
        ]);
        $details->emotional_behavioural_need = json_decode($details->emotional_behavioural_need);
        $details->cultural_religious_need = json_decode($details->cultural_religious_need);
        $details->eating_drinking_description = json_decode($details->eating_drinking_description);
        $details->support_peoples = json_decode($details->support_peoples);
        
        return response()->json([
            "message" => "Personal Details.",
            "data" => $details
        ]); 
    }

    public function getPersonalDetails() {
        $personal_details = PersonalDetail::where('user_id', Auth::user()->id)->get();
        foreach($personal_details as $details) {
            $details->emotional_behavioural_need = json_decode($details->emotional_behavioural_need);
            $details->cultural_religious_need = json_decode($details->cultural_religious_need);
            $details->eating_drinking_description = json_decode($details->eating_drinking_description);
            $details->support_peoples = json_decode($details->support_peoples);    
        }

        return response()->json([
            "message" => "Personal Details.",
            "data" => $personal_details
        ]); 
    }

    public function uploadPersonalDocuments(Request  $request) {
        $validate = Validator::make($request->all(), [
            'document_type' => 'required',
            'document.*' => 'required'
        ]);

        if ($validate->fails()) {
            return response([
                'message' => $validate->errors(),
            ], 422);
        }

        $files = $request->file('document');
        $uploads = [];
        foreach($files as $file) {
            $data = [];
            $data['document_type'] = $request->document_type;
            $this->uploadImage($file, $data);
            $data['user_id'] = Auth::user()->id;
            $document_upload = PersonalDocumentUpload::create($data);
            $document_upload->file_path = config('app.url').\Storage::url($document_upload->file_path);
            $uploads[] = $document_upload;
        }

        return response()->json([
            "message" => "Personal Documents.",
            "data" => $uploads
        ]); 

    }

    public function getUploadPersonalDocuments(Request  $request) {
        $files = PersonalDocumentUpload::where('user_id', Auth::user()->id)->get();
        
        foreach($files as $file) {
            $file->file_path = config('app.url').\Storage::url($file->file_path);
        }

        return response()->json([
            "message" => "Personal Documents.",
            "data" => $files
        ]); 

    }

    public function uploadImage($img, &$data)
    {
        $fileName = time().'.'.$img->extension();  
        $fileOrigName = $img->getClientOriginalName();
        $fileSize = $img->getSize();
        $mime = $img->getMimeType();
        $path = $img->storeAs('public/uploads',$fileName);
        $data['file_name'] = $fileOrigName;
        $data['file_path'] = $path;
        $data['file_mime'] = $mime;
        $data['file_size'] = $fileSize;

        return $path;
    }

}
