<?php

class InfiRestDoctrine_Field_ToMany
extends InfiRest_Endpoint_Field_Association_ToMany
{
	public function hydrate($fieldName, $bundle) {
		return parent::hydrate($fieldName, $bundle);
		return new \Doctrine\Common\Collections\ArrayCollection(
		);
	}
}