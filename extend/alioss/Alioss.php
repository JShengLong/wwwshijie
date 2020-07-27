<?php

namespace alioss;
/**
 * Created by PhpStorm.
 * User: jsl
 * Date: 2020/6/13
 * Time: 11:31
 */

use OSS\OssClient;
use OSS\Core\OssUtil;
use OSS\Core\OssException;
use think\Config;

class Alioss
{
    protected $accessKeyId     = "";
    protected $accessKeySecret = "";
    // Endpoint以杭州为例，其它Region请按实际情况填写。
    protected $endpoint = "http://oss-cn-hangzhou.aliyuncs.com";
    // 存储空间名称
    protected $bucket = "";


    public function __construct()
    {
        $config                = Config::get('ali_oss');
        $this->accessKeyId     = $config['accessKeyId'];
        $this->accessKeySecret = $config['accessKeySecret'];
        $this->bucket          = $config['bucket'];
    }

    public function oss($object,$filePath)
    {
        try {
            $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
            $res       = $ossClient->uploadFile($this->bucket, $object, $filePath);
            return $res;
        } catch (OssException $e) {
            return false;
        }
    }


    public function ossUtil($object, $uploadFile)
    {
        /**
         *  步骤1：初始化一个分片上传事件，获取uploadId。
         */
        try {
            $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
            //返回uploadId。uploadId是分片上传事件的唯一标识，您可以根据uploadId发起相关的操作，如取消分片上传、查询分片上传等。
            $uploadId = $ossClient->initiateMultipartUpload($this->bucket, $object);
        } catch (OssException $e) {
            return false;
        }
        /*
        * 步骤2：上传分片。
        */
        $partSize           = 10 * 1024 * 1024;
        $uploadFileSize     = filesize($uploadFile);
        $pieces             = $ossClient->generateMultiuploadParts($uploadFileSize, $partSize);
        $responseUploadPart = array();
        $uploadPosition     = 0;
        $isCheckMd5         = true;
        foreach ($pieces as $i => $piece) {
            $fromPos   = $uploadPosition + (integer)$piece[$ossClient::OSS_SEEK_TO];
            $toPos     = (integer)$piece[$ossClient::OSS_LENGTH] + $fromPos - 1;
            $upOptions = array(
                // 上传文件。
                $ossClient::OSS_FILE_UPLOAD => $uploadFile,
                // 设置分片号。
                $ossClient::OSS_PART_NUM    => ($i + 1),
                // 指定分片上传起始位置。
                $ossClient::OSS_SEEK_TO     => $fromPos,
                // 指定文件长度。
                $ossClient::OSS_LENGTH      => $toPos - $fromPos + 1,
                // 是否开启MD5校验，true为开启。
                $ossClient::OSS_CHECK_MD5   => $isCheckMd5,
            );
            // 开启MD5校验。
            if ($isCheckMd5) {
                $contentMd5                             = OssUtil::getMd5SumForFile($uploadFile, $fromPos, $toPos);
                $upOptions[$ossClient::OSS_CONTENT_MD5] = $contentMd5;
            }
            try {
                // 上传分片。
                $responseUploadPart[] = $ossClient->uploadPart($this->bucket, $object, $uploadId, $upOptions);
            } catch (OssException $e) {
                return false;
            }
        }
        // $uploadParts是由每个分片的ETag和分片号（PartNumber）组成的数组。
        $uploadParts = array();
        foreach ($responseUploadPart as $i => $eTag) {
            $uploadParts[] = array(
                'PartNumber' => ($i + 1),
                'ETag' => $eTag,
            );
        }
        /**
         * 步骤3：完成上传。
         */
        try {
            // 执行completeMultipartUpload操作时，需要提供所有有效的$uploadParts。OSS收到提交的$uploadParts后，会逐一验证每个分片的有效性。当所有的数据分片验证通过后，OSS将把这些分片组合成一个完整的文件。
            return $ossClient->completeMultipartUpload($this->bucket, $object, $uploadId, $uploadParts);
        }  catch(OssException $e) {
            return false;
        }
    }
}
