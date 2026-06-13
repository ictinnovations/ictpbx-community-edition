<?php

namespace ICT\Core\Api;

use ICT\Core\Api;
use ICT\Core\CoreException;
use ICT\Core\CallBlock;

#[\AllowDynamicProperties]
class CallBlockApi extends Api
{
  /** @url GET /call_block */
  public function list_view($query = array())
  {
    $this->_authorize_pbx('call_block');
    $filter = array_merge((array)$query, $this->_tenant_filter());
    return CallBlock::search($filter);
  }

  /** @url GET /call_block/$call_block_uuid */
  public function read($call_block_uuid)
  {
    $this->_authorize_pbx('call_block');
    $o = new CallBlock($call_block_uuid);
    $this->_assert_pbx_domain($o);
    return $o;
  }

  /** @url POST /call_block */
  public function create($data = array())
  {
    $this->_authorize_pbx('call_block');
    $o = new CallBlock();
    $o->tenant_id = $this->oUser->tenant_id;
    $this->set($o, $data);
    $uuid = $o->save();
    if ($uuid) { return $uuid; }
    throw new CoreException(417, 'Call Block creation failed');
  }

  /** @url PUT /call_block/$call_block_uuid */
  public function update($call_block_uuid, $data = array())
  {
    $this->_authorize_pbx('call_block');
    $o = new CallBlock($call_block_uuid);
    $this->_assert_pbx_domain($o);
    $this->set($o, $data);
    if ($o->save()) { return $o; }
    throw new CoreException(417, 'Call Block update failed');
  }

  /** @url DELETE /call_block/$call_block_uuid */
  public function remove($call_block_uuid)
  {
    $this->_authorize_pbx('call_block');
    $o = new CallBlock($call_block_uuid);
    $this->_assert_pbx_domain($o);
    return $o->delete();
  }
}
